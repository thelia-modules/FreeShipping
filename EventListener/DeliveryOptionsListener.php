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
use Thelia\Api\Bridge\Propel\Event\DeliveryModuleOptionEvent;
use Thelia\Core\Event\TheliaEvents;
use Thelia\Model\Cart;
use Thelia\Model\Country;

/**
 * The price shown at the delivery step and returned by /api/front/delivery_modules
 * does not come from the postage event: each delivery module fills its own
 * options. This listener offers those options too, so the tunnel never shows a
 * price it will not charge.
 */
final readonly class DeliveryOptionsListener implements EventSubscriberInterface
{
    /**
     * Below the 129 that CustomDelivery — and every module that copies it —
     * uses to append its options: there has to be an option before it can be
     * offered.
     */
    public const PRIORITY = 0;

    public function __construct(
        private FreeShippingEvaluator $evaluator,
        private LoggerInterface $logger,
    ) {
    }

    public function applyFreeShipping(DeliveryModuleOptionEvent $event): void
    {
        try {
            $cart = $event->getCart();

            if (!$cart instanceof Cart) {
                return;
            }

            $country = $event->getCountry() ?? $event->getAddress()?->getCountry();

            if (!$country instanceof Country) {
                return;
            }

            $decision = $this->evaluator->evaluate(
                $cart,
                $country,
                $event->getState(),
                $event->getModule()->getId(),
            );

            if (!$decision->isFree()) {
                return;
            }

            foreach ($event->getDeliveryModuleOptions() as $option) {
                if (!$option->isValid()) {
                    continue;
                }

                $option
                    ->setPostage(0.0)
                    ->setPostageTax(0.0)
                    ->setPostageUntaxed(0.0);
            }
        } catch (\Throwable $throwable) {
            $this->logger->error('Free shipping could not be applied to the delivery options, the carrier prices are kept: '.$throwable->getMessage(), ['exception' => $throwable]);
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            TheliaEvents::MODULE_DELIVERY_GET_OPTIONS => ['applyFreeShipping', self::PRIORITY],
        ];
    }
}
