<?php

declare(strict_types=1);

/*
 * This file is part of the Thelia package.
 * http://www.thelia.net
 *
 * (c) OpenStudio <info@thelia.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FreeShipping\Tests\Integration;

use FreeShipping\EventListener\DeliveryOptionsListener;
use FreeShipping\EventListener\PostageListener;
use FreeShipping\FreeShipping;
use FreeShipping\Model\FreeShippingRule;
use FreeShipping\Repository\FreeShippingRuleRepository;
use FreeShipping\Service\FreeShippingEvaluator;
use FreeShipping\Service\FreeShippingSettings;
use Psr\Log\NullLogger;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Thelia\Api\Bridge\Propel\Event\DeliveryModuleOptionEvent;
use Thelia\Api\Resource\DeliveryModuleOption;
use Thelia\Core\Event\Delivery\DeliveryPostageEvent;
use Thelia\Core\Event\TheliaEvents;
use Thelia\Model\Area;
use Thelia\Model\Cart;
use Thelia\Model\Country;
use Thelia\Model\CountryArea;
use Thelia\Model\Module;
use Thelia\Model\ModuleQuery;
use Thelia\Model\OrderPostage;
use Thelia\Test\FixtureFactory;
use Thelia\Test\IntegrationTestCase;

/**
 * Both listeners run after the delivery module has priced the shipment, and
 * neither is allowed to throw: the checkout swallows exceptions raised while
 * the postage is computed, so a bad rule would silently break the tunnel.
 */
final class FreeShippingListenersTest extends IntegrationTestCase
{
    private FixtureFactory $factory;
    private Country $country;
    private Area $area;
    private Module $deliveryModule;

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = $this->createFixtureFactory();
        $this->country = $this->factory->country(['isocode' => '250', 'isoalpha2' => 'FR', 'isoalpha3' => 'FRA']);
        $this->area = new Area();
        $this->area->setName('Listener area')->save($this->getPropelConnection());
        (new CountryArea())
            ->setAreaId($this->area->getId())
            ->setCountryId($this->country->getId())
            ->save($this->getPropelConnection());

        $this->deliveryModule = ModuleQuery::create()->findOneByCode('CustomDelivery')
            ?? self::fail('The CustomDelivery module is not registered in the test database.');

        FreeShipping::setConfigValue(FreeShipping::CONFIG_THRESHOLD_INCLUDES_TAXES, '1');
        FreeShipping::setConfigValue(FreeShipping::CONFIG_DEDUCT_DISCOUNTS, '1');
    }

    public function testThePostageListenerRunsAfterTheDeliveryModules(): void
    {
        $subscribed = PostageListener::getSubscribedEvents();

        self::assertLessThan(128, $subscribed[TheliaEvents::MODULE_DELIVERY_GET_POSTAGE][1]);
    }

    public function testTheOptionsListenerRunsAfterTheDeliveryModules(): void
    {
        $subscribed = DeliveryOptionsListener::getSubscribedEvents();

        self::assertLessThan(129, $subscribed[TheliaEvents::MODULE_DELIVERY_GET_OPTIONS][1]);
    }

    public function testAFreeShippingRuleZeroesThePostageAndKeepsItsTaxRuleTitle(): void
    {
        $cart = $this->cart(50.0);
        $this->rule(10.0);

        $event = $this->postageEvent($cart, 6.9, 1.15);
        $this->postageListener()->applyFreeShipping($event);

        self::assertSame(0.0, (float) $event->getPostage()?->getAmount());
        self::assertSame(0.0, (float) $event->getPostage()?->getAmountTax());
        self::assertSame('VAT 20%', $event->getPostage()?->getTaxRuleTitle());
    }

    public function testWithoutARuleThePostageIsLeftAlone(): void
    {
        $event = $this->postageEvent($this->cart(50.0), 6.9, 1.15);

        $this->postageListener()->applyFreeShipping($event);

        self::assertSame(6.9, (float) $event->getPostage()?->getAmount());
    }

    public function testAnInvalidDeliveryModuleIsLeftAlone(): void
    {
        $cart = $this->cart(50.0);
        $this->rule(10.0);

        $event = $this->postageEvent($cart, 6.9, 1.15);
        $event->setValidModule(false);

        $this->postageListener()->applyFreeShipping($event);

        self::assertSame(6.9, (float) $event->getPostage()?->getAmount());
    }

    public function testAFreeShippingRuleZeroesTheDeliveryOptionThatTheFrontReads(): void
    {
        $cart = $this->cart(50.0);
        $this->rule(10.0);

        $event = $this->optionEvent($cart);
        $this->optionsListener()->applyFreeShipping($event);

        $option = $event->getDeliveryModuleOptions()[0];

        self::assertSame(0.0, $option->getPostage());
        self::assertSame(0.0, $option->getPostageTax());
        self::assertSame(0.0, $option->getPostageUntaxed());
    }

    public function testWithoutARuleTheDeliveryOptionKeepsItsPrice(): void
    {
        $event = $this->optionEvent($this->cart(50.0));

        $this->optionsListener()->applyFreeShipping($event);

        self::assertSame(6.9, $event->getDeliveryModuleOptions()[0]->getPostage());
    }

    public function testBothListenersAreWiredOnTheDispatcherAtTheirPriority(): void
    {
        /** @var EventDispatcherInterface $dispatcher */
        $dispatcher = static::getContainer()->get('event_dispatcher');

        self::assertSame(
            PostageListener::PRIORITY,
            $this->priorityOf($dispatcher, TheliaEvents::MODULE_DELIVERY_GET_POSTAGE, PostageListener::class),
        );
        self::assertSame(
            DeliveryOptionsListener::PRIORITY,
            $this->priorityOf($dispatcher, TheliaEvents::MODULE_DELIVERY_GET_OPTIONS, DeliveryOptionsListener::class),
        );
    }

    // ------------------------------------------------------------------

    private function priorityOf(object $dispatcher, string $eventName, string $listenerClass): ?int
    {
        foreach ($dispatcher->getListeners($eventName) as $listener) {
            if (\is_array($listener) && $listener[0] instanceof $listenerClass) {
                return $dispatcher->getListenerPriority($eventName, $listener);
            }
        }

        return null;
    }

    private function postageListener(): PostageListener
    {
        return new PostageListener($this->evaluator(), new NullLogger());
    }

    private function optionsListener(): DeliveryOptionsListener
    {
        return new DeliveryOptionsListener($this->evaluator(), new NullLogger());
    }

    private function evaluator(): FreeShippingEvaluator
    {
        return new FreeShippingEvaluator(new FreeShippingRuleRepository(), new FreeShippingSettings());
    }

    private function postageEvent(Cart $cart, float $amount, float $tax): DeliveryPostageEvent
    {
        $module = static::getContainer()->get('module.'.$this->deliveryModule->getCode());

        $postage = new OrderPostage();
        $postage->setAmount($amount);
        $postage->setAmountTax($tax);
        $postage->setTaxRuleTitle('VAT 20%');

        $event = new DeliveryPostageEvent($module, $cart, null, $this->country, null);
        $event->setValidModule(true)->setPostage($postage);

        return $event;
    }

    private function optionEvent(Cart $cart): DeliveryModuleOptionEvent
    {
        $event = new DeliveryModuleOptionEvent($this->deliveryModule, null, $cart, $this->country, null);

        $option = new DeliveryModuleOption();
        $option
            ->setCode($this->deliveryModule->getCode())
            ->setValid(true)
            ->setTitle('Custom Delivery')
            ->setImage('')
            ->setPostage(6.9)
            ->setPostageTax(1.15)
            ->setPostageUntaxed(5.75);

        $event->appendDeliveryModuleOptions($option);

        return $event;
    }

    private function cart(float $untaxedTotal): Cart
    {
        $currency = $this->factory->currency();
        $category = $this->factory->category();
        $taxRule = $this->factory->taxRule();
        $product = $this->factory->product($category, $taxRule, $currency, ['basePrice' => $untaxedTotal]);

        $cart = $this->factory->cart(null, ['currency' => $currency]);
        $this->factory->cartItem($cart, $product, null, [
            'quantity' => 1.0,
            'price' => number_format($untaxedTotal, 6, '.', ''),
            'promoPrice' => number_format($untaxedTotal, 6, '.', ''),
        ]);

        return $cart;
    }

    private function rule(float $threshold): FreeShippingRule
    {
        $rule = new FreeShippingRule();
        $rule
            ->setAreaId($this->area->getId())
            ->setThreshold(number_format($threshold, 6, '.', ''))
            ->setActive(1)
            ->save($this->getPropelConnection());

        return $rule;
    }
}
