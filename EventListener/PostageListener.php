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

namespace FreeShipping\EventListener;

use FreeShipping\Service\FreeShippingEvaluator;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Thelia\Core\Event\Delivery\DeliveryPostageEvent;
use Thelia\Core\Event\TheliaEvents;
use Thelia\Model\Country;
use Thelia\Model\OrderPostage;

/**
 * Zeroes the postage the carrier has just computed when a free shipping rule
 * applies. The carrier is still the one the customer chose, and its tax rule
 * title is kept so the invoice keeps naming the same rule.
 *
 * Nothing here is allowed to throw: the checkout wraps the postage computation
 * in a catch-all that empties the cart postage, and the cart action swallows
 * exceptions outright, so a raised error would show up as a silently wrong
 * price rather than as a failure.
 */
final readonly class PostageListener implements EventSubscriberInterface
{
    /**
     * Below the 128 of Thelia\Action\Delivery, which is what asks the carrier
     * for its price: there has to be a price before it can be offered.
     */
    public const PRIORITY = 64;

    public function __construct(
        private FreeShippingEvaluator $evaluator,
        private LoggerInterface $logger,
    ) {
    }

    public function applyFreeShipping(DeliveryPostageEvent $event): void
    {
        try {
            if (!$event->isValidModule()) {
                return;
            }

            $postage = $event->getPostage();

            if (!$postage instanceof OrderPostage) {
                return;
            }

            $country = $event->getCountry();

            if (!$country instanceof Country) {
                return;
            }

            $decision = $this->evaluator->evaluate(
                $event->getCart(),
                $country,
                $event->getState(),
                $this->deliveryModuleId($event),
            );

            if (!$decision->isFree()) {
                return;
            }

            $free = new OrderPostage();
            $free->setAmount(0.0);
            $free->setAmountTax(0.0);
            $free->setTaxRuleTitle($postage->getTaxRuleTitle());

            $event->setPostage($free);
        } catch (\Throwable $throwable) {
            $this->logger->error('Free shipping could not be applied to the postage, the carrier price is kept: '.$throwable->getMessage(), ['exception' => $throwable]);
        }
    }

    private function deliveryModuleId(DeliveryPostageEvent $event): ?int
    {
        try {
            return $event->getModule()->getModuleModel()->getId();
        } catch (\Throwable) {
            return null;
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            TheliaEvents::MODULE_DELIVERY_GET_POSTAGE => ['applyFreeShipping', self::PRIORITY],
        ];
    }
}
