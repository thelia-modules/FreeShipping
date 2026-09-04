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

/**
 * What the evaluator answers: whether shipping is offered, which rule says so,
 * and, when it is not offered yet, the lowest threshold the cart is heading for.
 *
 * The checkout, the delivery options and the cart message all read this same
 * object, which is what keeps the price announced and the price ordered equal.
 */
final readonly class FreeShippingDecision
{
    private function __construct(
        private bool $free,
        private ?FreeShippingRule $rule,
        private ?float $threshold,
        private float $comparedAmount,
    ) {
    }

    /**
     * No rule applies: the shop prices this cart as it always did.
     */
    public static function none(float $comparedAmount): self
    {
        return new self(false, null, null, $comparedAmount);
    }

    /**
     * The threshold is reached: shipping is offered.
     */
    public static function granted(?FreeShippingRule $rule, float $threshold, float $comparedAmount): self
    {
        return new self(true, $rule, $threshold, $comparedAmount);
    }

    /**
     * A rule applies but the cart has not reached its threshold yet.
     */
    public static function pending(?FreeShippingRule $rule, float $threshold, float $comparedAmount): self
    {
        return new self(false, $rule, $threshold, $comparedAmount);
    }

    public function isFree(): bool
    {
        return $this->free;
    }

    public function getRule(): ?FreeShippingRule
    {
        return $this->rule;
    }

    public function getThreshold(): ?float
    {
        return $this->threshold;
    }

    public function getComparedAmount(): float
    {
        return $this->comparedAmount;
    }

    /**
     * What the cart still has to add to be offered its shipping, or null when
     * there is nothing to announce: either shipping is already offered, or no
     * rule applies at all.
     */
    public function getRemainingAmount(): ?float
    {
        if ($this->free || null === $this->threshold) {
            return null;
        }

        return round(max(0.0, $this->threshold - $this->comparedAmount), 2);
    }
}
