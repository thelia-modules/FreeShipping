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

use FreeShipping\Model\FreeShippingRule;
use FreeShipping\Repository\FreeShippingRuleRepository;
use Thelia\Model\Cart;
use Thelia\Model\Country;
use Thelia\Model\CountryArea;
use Thelia\Model\CountryAreaQuery;
use Thelia\Model\State;

/**
 * Decides whether a cart is shipped for free, and what it would take to be.
 *
 * The rules are read once per evaluation and filtered in PHP, so pricing five
 * carriers costs one query, not five. The evaluation never depends on an
 * address: the postage estimator has none to give.
 */
final readonly class FreeShippingEvaluator
{
    /**
     * Money compared as floats: a cart worth exactly the threshold has to be
     * offered its shipping, whatever the last bit of the decimal says.
     */
    private const AMOUNT_EPSILON = 0.0001;

    public function __construct(
        private FreeShippingRuleRepository $rules,
        private FreeShippingSettings $settings,
    ) {
    }

    /**
     * @param ?int $deliveryModuleId the carrier being priced, or null to look at
     *                               every carrier, which is what the cart message does
     */
    public function evaluate(
        Cart $cart,
        ?Country $country = null,
        ?State $state = null,
        ?int $deliveryModuleId = null,
    ): FreeShippingDecision {
        $comparedAmount = $this->comparedAmount($cart, $country, $state);
        $candidates = $this->applicableRules($country, $state, $deliveryModuleId);

        $best = $this->lowestThreshold($candidates);

        if (!$best instanceof FreeShippingRule) {
            return FreeShippingDecision::none($comparedAmount);
        }

        $threshold = (float) $best->getThreshold();

        return $comparedAmount + self::AMOUNT_EPSILON >= $threshold
            ? FreeShippingDecision::granted($best, $threshold, $comparedAmount)
            : FreeShippingDecision::pending($best, $threshold, $comparedAmount);
    }

    /**
     * The products of the cart, never its postage. Which total that is depends
     * on the two settings the back office explains.
     */
    private function comparedAmount(Cart $cart, ?Country $country, ?State $state): float
    {
        $deductDiscounts = $this->settings->deductDiscounts();

        if (!$this->settings->thresholdIncludesTaxes()) {
            return $cart->getTotalAmount($deductDiscounts, $country, $state);
        }

        $taxCountry = $country ?? $this->shopCountry();

        // Without a country there is no VAT rate to apply, so the untaxed total
        // is the only honest answer. It only happens on the cart message, before
        // any address is known.
        return $taxCountry instanceof Country
            ? $cart->getTaxedAmount($taxCountry, $deductDiscounts, $state)
            : $cart->getTotalAmount($deductDiscounts);
    }

    private function shopCountry(): ?Country
    {
        try {
            $country = Country::getShopLocation();
        } catch (\Throwable) {
            return null;
        }

        return $country instanceof Country ? $country : null;
    }

    /**
     * @return FreeShippingRule[]
     */
    private function applicableRules(?Country $country, ?State $state, ?int $deliveryModuleId): array
    {
        $areaIds = $country instanceof Country ? $this->areaIdsOf($country, $state) : null;

        if ([] === $areaIds) {
            return [];
        }

        $now = new \DateTime();
        $applicable = [];

        foreach ($this->rules->findActive() as $rule) {
            if (!$this->isInPeriod($rule, $now)) {
                continue;
            }

            if (null !== $areaIds && !\in_array((int) $rule->getAreaId(), $areaIds, true)) {
                continue;
            }

            $ruleModuleId = $rule->getDeliveryModuleId();

            // A rule tied to one carrier only answers for that carrier. With no
            // carrier to answer for, every rule counts: the cart message speaks
            // for the whole shop.
            if (null !== $ruleModuleId && null !== $deliveryModuleId && $ruleModuleId !== $deliveryModuleId) {
                continue;
            }

            $applicable[] = $rule;
        }

        return $applicable;
    }

    /**
     * @return int[]
     */
    private function areaIdsOf(Country $country, ?State $state): array
    {
        $areaIds = [];

        /** @var CountryArea $countryArea */
        foreach (CountryAreaQuery::findByCountryAndState($country, $state) as $countryArea) {
            $areaIds[] = (int) $countryArea->getAreaId();
        }

        return $areaIds;
    }

    /**
     * Both bounds are included, and an empty bound means "since always" or
     * "until further notice".
     */
    private function isInPeriod(FreeShippingRule $rule, \DateTimeInterface $now): bool
    {
        $start = $rule->getStartDate();
        $end = $rule->getEndDate();

        if ($start instanceof \DateTimeInterface && $start > $now) {
            return false;
        }

        return !($end instanceof \DateTimeInterface && $end < $now);
    }

    /**
     * @param FreeShippingRule[] $rules
     */
    private function lowestThreshold(array $rules): ?FreeShippingRule
    {
        $lowest = null;

        foreach ($rules as $rule) {
            if (null === $lowest || (float) $rule->getThreshold() < (float) $lowest->getThreshold()) {
                $lowest = $rule;
            }
        }

        return $lowest;
    }
}
