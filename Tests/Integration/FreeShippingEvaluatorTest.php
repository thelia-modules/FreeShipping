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

use FreeShipping\FreeShipping;
use FreeShipping\Model\FreeShippingRule;
use FreeShipping\Repository\FreeShippingRuleRepository;
use FreeShipping\Service\FreeShippingDecision;
use FreeShipping\Service\FreeShippingEvaluator;
use FreeShipping\Service\FreeShippingSettings;
use Thelia\Model\Area;
use Thelia\Model\Cart;
use Thelia\Model\Country;
use Thelia\Model\CountryArea;
use Thelia\Model\Module;
use Thelia\Model\ModuleQuery;
use Thelia\Model\TaxRule;
use Thelia\Model\TaxRuleCountry;
use Thelia\Test\FixtureFactory;
use Thelia\Test\IntegrationTestCase;

/**
 * The evaluator is the single source of truth for the free shipping offer: the
 * checkout, the API and the cart message all read the same decision.
 */
final class FreeShippingEvaluatorTest extends IntegrationTestCase
{
    private FixtureFactory $factory;
    private Country $country;
    private Area $area;
    private TaxRule $taxRule;

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = $this->createFixtureFactory();
        $this->country = $this->factory->country(['isocode' => '250', 'isoalpha2' => 'FR', 'isoalpha3' => 'FRA']);
        $this->taxRule = $this->vatRule('20.000000');
        $this->area = $this->area('Test area');
        $this->attach($this->area, $this->country);

        // The defaults the back office ships with, restated so a test never
        // depends on what a previous run left in module_config.
        FreeShipping::setConfigValue(FreeShipping::CONFIG_THRESHOLD_INCLUDES_TAXES, '1');
        FreeShipping::setConfigValue(FreeShipping::CONFIG_DEDUCT_DISCOUNTS, '1');
    }

    public function testACartBelowTheThresholdKeepsItsPostageAndKnowsWhatIsLeft(): void
    {
        $cart = $this->cart(50.0);
        $this->rule($this->area, 100.0);

        $decision = $this->evaluate($cart);

        self::assertFalse($decision->isFree());
        self::assertSame(60.0, $decision->getComparedAmount());
        self::assertSame(100.0, $decision->getThreshold());
        self::assertSame(40.0, $decision->getRemainingAmount());
    }

    public function testACartExactlyOnTheThresholdIsFree(): void
    {
        $cart = $this->cart(50.0);
        $this->rule($this->area, 60.0);

        self::assertTrue($this->evaluate($cart)->isFree());
    }

    public function testACartAboveTheThresholdIsFree(): void
    {
        $cart = $this->cart(50.0);
        $rule = $this->rule($this->area, 50.0);

        $decision = $this->evaluate($cart);

        self::assertTrue($decision->isFree());
        self::assertSame($rule->getId(), $decision->getRule()?->getId());
        self::assertNull($decision->getRemainingAmount());
    }

    public function testARuleOnAnotherAreaDoesNotApply(): void
    {
        $cart = $this->cart(50.0);
        $this->rule($this->area('Another area'), 10.0);

        $decision = $this->evaluate($cart);

        self::assertFalse($decision->isFree());
        self::assertNull($decision->getThreshold());
    }

    public function testARuleRestrictedToACarrierLeavesTheOtherCarriersAlone(): void
    {
        $cart = $this->cart(50.0);
        $chosen = $this->deliveryModule('CustomDelivery');
        $other = $this->deliveryModule('VirtualProductDelivery');
        $this->rule($this->area, 10.0, $chosen);

        self::assertTrue($this->evaluate($cart, $chosen->getId())->isFree());
        self::assertFalse($this->evaluate($cart, $other->getId())->isFree());
    }

    public function testARuleOpenToEveryCarrierAppliesToTheCarrierBeingEvaluated(): void
    {
        $cart = $this->cart(50.0);
        $this->rule($this->area, 10.0);

        self::assertTrue($this->evaluate($cart, $this->deliveryModule('CustomDelivery')->getId())->isFree());
    }

    public function testARuleOutOfItsPeriodDoesNotApply(): void
    {
        $cart = $this->cart(50.0);
        $rule = $this->rule($this->area, 10.0);

        $rule->setStartDate(new \DateTime('-10 days'))->setEndDate(new \DateTime('-1 day'))->save();
        self::assertFalse($this->evaluate($cart)->isFree());

        $rule->setStartDate(new \DateTime('+1 day'))->setEndDate(null)->save();
        self::assertFalse($this->evaluate($cart)->isFree());
    }

    public function testTheBoundsOfThePeriodAreIncluded(): void
    {
        $cart = $this->cart(50.0);
        $rule = $this->rule($this->area, 10.0);

        $rule->setStartDate(new \DateTime('today 00:00:00'))->setEndDate(new \DateTime('today 23:59:59'))->save();

        self::assertTrue($this->evaluate($cart)->isFree());
    }

    public function testAnInactiveRuleDoesNotApply(): void
    {
        $cart = $this->cart(50.0);
        $this->rule($this->area, 10.0)->setActive(0)->save();

        self::assertFalse($this->evaluate($cart)->isFree());
    }

    public function testTheTaxSettingDecidesWhichTotalIsComparedWithTheThreshold(): void
    {
        $cart = $this->cart(50.0);
        $this->rule($this->area, 55.0);

        FreeShipping::setConfigValue(FreeShipping::CONFIG_THRESHOLD_INCLUDES_TAXES, '1');
        $taxIncluded = $this->evaluate($cart);
        self::assertSame(60.0, $taxIncluded->getComparedAmount());
        self::assertTrue($taxIncluded->isFree());

        FreeShipping::setConfigValue(FreeShipping::CONFIG_THRESHOLD_INCLUDES_TAXES, '0');
        $taxExcluded = $this->evaluate($cart);
        self::assertSame(50.0, $taxExcluded->getComparedAmount());
        self::assertFalse($taxExcluded->isFree());
    }

    public function testTheDiscountSettingDecidesWhetherDiscountsCountTowardsTheThreshold(): void
    {
        $cart = $this->cart(50.0);
        $cart->setDiscount('15.000000')->save();
        $this->rule($this->area, 55.0);

        FreeShipping::setConfigValue(FreeShipping::CONFIG_DEDUCT_DISCOUNTS, '1');
        self::assertSame(45.0, $this->evaluate($cart)->getComparedAmount());
        self::assertFalse($this->evaluate($cart)->isFree());

        FreeShipping::setConfigValue(FreeShipping::CONFIG_DEDUCT_DISCOUNTS, '0');
        self::assertSame(60.0, $this->evaluate($cart)->getComparedAmount());
        self::assertTrue($this->evaluate($cart)->isFree());
    }

    public function testTheLowestApplicableThresholdWins(): void
    {
        $cart = $this->cart(50.0);
        $this->rule($this->area, 80.0);
        $lowest = $this->rule($this->area, 50.0);

        $decision = $this->evaluate($cart);

        self::assertTrue($decision->isFree());
        self::assertSame($lowest->getId(), $decision->getRule()?->getId());
    }

    public function testTheLowestUnreachedThresholdIsTheOneAnnounced(): void
    {
        $cart = $this->cart(50.0);
        $this->rule($this->area, 100.0);
        $this->rule($this->area, 80.0);

        $decision = $this->evaluate($cart);

        self::assertSame(80.0, $decision->getThreshold());
        self::assertSame(20.0, $decision->getRemainingAmount());
    }

    public function testDeletingTheAreaTakesItsRulesWithIt(): void
    {
        $cart = $this->cart(50.0);
        $area = $this->area('Doomed area');
        $this->attach($area, $this->country);
        $this->rule($area, 10.0);

        $area->delete();

        $decision = $this->evaluate($cart);

        self::assertFalse($decision->isFree());
        self::assertNull($decision->getThreshold());
    }

    public function testWithoutACountryTheLowestThresholdOfTheShopIsAnnounced(): void
    {
        $cart = $this->cart(50.0);
        $this->rule($this->area, 200.0);
        $this->rule($this->area('Far away'), 90.0);

        $decision = $this->evaluator()->evaluate($cart, null, null, null);

        self::assertFalse($decision->isFree());
        self::assertSame(90.0, $decision->getThreshold());
    }

    public function testWithoutAnyRuleTheDecisionIsEmpty(): void
    {
        $decision = $this->evaluate($this->cart(50.0));

        self::assertFalse($decision->isFree());
        self::assertNull($decision->getRule());
        self::assertNull($decision->getThreshold());
    }

    // ------------------------------------------------------------------

    private function evaluator(): FreeShippingEvaluator
    {
        // Built by hand rather than fetched: a module service is private, and
        // Symfony inlines a private service used once, which takes it out of
        // the test container. Wiring is covered by FreeShippingListenersTest.
        return new FreeShippingEvaluator(new FreeShippingRuleRepository(), new FreeShippingSettings());
    }

    private function evaluate(Cart $cart, ?int $deliveryModuleId = null): FreeShippingDecision
    {
        return $this->evaluator()->evaluate($cart, $this->country, null, $deliveryModuleId);
    }

    private function cart(float $untaxedTotal): Cart
    {
        $currency = $this->factory->currency();
        $category = $this->factory->category();
        $product = $this->factory->product($category, $this->taxRule, $currency, ['basePrice' => $untaxedTotal]);

        $cart = $this->factory->cart(null, ['currency' => $currency]);
        $this->factory->cartItem($cart, $product, null, [
            'quantity' => 1.0,
            'price' => number_format($untaxedTotal, 6, '.', ''),
            'promoPrice' => number_format($untaxedTotal, 6, '.', ''),
        ]);

        return $cart;
    }

    private function rule(Area $area, float $threshold, ?Module $module = null): FreeShippingRule
    {
        $rule = new FreeShippingRule();
        $rule
            ->setAreaId($area->getId())
            ->setDeliveryModuleId($module?->getId())
            ->setThreshold(number_format($threshold, 6, '.', ''))
            ->setActive(1)
            ->save($this->getPropelConnection());

        return $rule;
    }

    private function area(string $name): Area
    {
        $area = new Area();
        $area->setName($name);
        $area->save($this->getPropelConnection());

        return $area;
    }

    private function attach(Area $area, Country $country): void
    {
        (new CountryArea())
            ->setAreaId($area->getId())
            ->setCountryId($country->getId())
            ->save($this->getPropelConnection());
    }

    private function deliveryModule(string $code): Module
    {
        return ModuleQuery::create()->findOneByCode($code)
            ?? self::fail(\sprintf('The %s module is not registered in the test database.', $code));
    }

    private function vatRule(string $percent): TaxRule
    {
        // A non-empty override forces a rule of its own instead of reusing the seeded one.
        $taxRule = $this->factory->taxRule(['isDefault' => false]);
        $tax = $this->factory->tax(['requirements' => ['percent' => $percent], 'title' => 'VAT '.$percent]);

        (new TaxRuleCountry())
            ->setTaxRuleId($taxRule->getId())
            ->setCountryId($this->country->getId())
            ->setTaxId($tax->getId())
            ->setPosition(1)
            ->save($this->getPropelConnection());

        return $taxRule;
    }
}
