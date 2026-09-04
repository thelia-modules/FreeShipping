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

namespace FreeShipping\Service;

use FreeShipping\FreeShipping;
use FreeShipping\Repository\FreeShippingRuleRepository;
use Thelia\Core\Translation\Translator;
use Thelia\Model\AreaQuery;
use Thelia\Model\ModuleQuery;

/**
 * Turns the rule rows into what the back office table displays: names rather
 * than identifiers, dates rather than timestamps.
 */
final readonly class RulePresenter
{
    public function __construct(private FreeShippingRuleRepository $rules)
    {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function rows(string $locale): array
    {
        $areas = $this->areaNames();
        $carriers = $this->carrierNames($locale);
        $everyCarrier = Translator::getInstance()->trans('Every carrier', [], FreeShipping::DOMAIN_NAME, $locale);

        $rows = [];

        foreach ($this->rules->findAll() as $rule) {
            $moduleId = $rule->getDeliveryModuleId();

            $rows[] = [
                'id' => $rule->getId(),
                'area_id' => $rule->getAreaId(),
                'area' => $areas[(int) $rule->getAreaId()] ?? '',
                'delivery_module_id' => $moduleId,
                'carrier' => null === $moduleId ? $everyCarrier : ($carriers[$moduleId] ?? ''),
                'threshold' => (float) $rule->getThreshold(),
                'start_date' => $rule->getStartDate('Y-m-d'),
                'end_date' => $rule->getEndDate('Y-m-d'),
                'active' => 1 === (int) $rule->getActive(),
                'position' => $rule->getPosition(),
            ];
        }

        return $rows;
    }

    /**
     * @return array<int, string>
     */
    private function areaNames(): array
    {
        $names = [];

        foreach (AreaQuery::create()->find() as $area) {
            $names[(int) $area->getId()] = (string) $area->getName();
        }

        return $names;
    }

    /**
     * @return array<int, string>
     */
    private function carrierNames(string $locale): array
    {
        $names = [];

        foreach (ModuleQuery::create()->filterByType(2)->find() as $module) {
            $names[(int) $module->getId()] = (string) ($module->setLocale($locale)->getTitle() ?? $module->getCode());
        }

        return $names;
    }
}
