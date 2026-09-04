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

namespace FreeShipping\Action;

use FreeShipping\Event\FreeShippingEvents;
use FreeShipping\Event\RuleEvent;
use FreeShipping\Event\SettingsEvent;
use FreeShipping\Model\FreeShippingRule;
use FreeShipping\Repository\FreeShippingRuleRepository;
use FreeShipping\Service\FreeShippingSettings;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * The only place a rule or a setting is written.
 */
final readonly class FreeShippingAction implements EventSubscriberInterface
{
    public function __construct(
        private FreeShippingRuleRepository $rules,
        private FreeShippingSettings $settings,
    ) {
    }

    public function createRule(RuleEvent $event): void
    {
        $event->setRule($this->save(new FreeShippingRule(), $event));
    }

    public function updateRule(RuleEvent $event): void
    {
        $rule = $this->ruleOf($event);

        if (!$rule instanceof FreeShippingRule) {
            return;
        }

        $event->setRule($this->save($rule, $event));
    }

    public function deleteRule(RuleEvent $event): void
    {
        $rule = $this->ruleOf($event);

        if (!$rule instanceof FreeShippingRule) {
            return;
        }

        $rule->delete();
        $event->setRule($rule);
    }

    public function updateSettings(SettingsEvent $event): void
    {
        $this->settings->save($event->thresholdIncludesTaxes(), $event->deductDiscounts());
    }

    private function ruleOf(RuleEvent $event): ?FreeShippingRule
    {
        $id = $event->getId();

        return null === $id ? null : $this->rules->find($id);
    }

    private function save(FreeShippingRule $rule, RuleEvent $event): FreeShippingRule
    {
        $rule
            ->setAreaId($event->getAreaId())
            ->setDeliveryModuleId($event->getDeliveryModuleId())
            ->setThreshold(number_format($event->getThreshold(), 6, '.', ''))
            ->setStartDate($event->getStartDate())
            ->setEndDate($event->getEndDate())
            ->setActive($event->isActive() ? 1 : 0)
            ->setPosition($event->getPosition())
            ->save();

        return $rule;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            FreeShippingEvents::RULE_CREATE => ['createRule', 128],
            FreeShippingEvents::RULE_UPDATE => ['updateRule', 128],
            FreeShippingEvents::RULE_DELETE => ['deleteRule', 128],
            FreeShippingEvents::SETTINGS_UPDATE => ['updateSettings', 128],
        ];
    }
}
