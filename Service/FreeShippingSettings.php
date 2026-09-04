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

/**
 * The two settings that decide which cart total is compared with a threshold.
 *
 * Both default to what a French B2C shop expects: a tax included total, with
 * discounts already subtracted.
 */
final readonly class FreeShippingSettings
{
    public function thresholdIncludesTaxes(): bool
    {
        return $this->readFlag(FreeShipping::CONFIG_THRESHOLD_INCLUDES_TAXES);
    }

    public function deductDiscounts(): bool
    {
        return $this->readFlag(FreeShipping::CONFIG_DEDUCT_DISCOUNTS);
    }

    public function save(bool $thresholdIncludesTaxes, bool $deductDiscounts): void
    {
        FreeShipping::setConfigValue(FreeShipping::CONFIG_THRESHOLD_INCLUDES_TAXES, $thresholdIncludesTaxes ? '1' : '0');
        FreeShipping::setConfigValue(FreeShipping::CONFIG_DEDUCT_DISCOUNTS, $deductDiscounts ? '1' : '0');
    }

    /**
     * A setting turned off is stored as the string "0", which PHP reads as
     * false on its own: only an absent setting falls back to the default.
     */
    private function readFlag(string $key): bool
    {
        try {
            $value = FreeShipping::getConfigValue($key);
        } catch (\Throwable) {
            return true;
        }

        if (null === $value || '' === $value) {
            return true;
        }

        return '0' !== $value;
    }
}
