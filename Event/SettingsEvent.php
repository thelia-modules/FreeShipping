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

namespace FreeShipping\Event;

use Thelia\Core\Event\ActionEvent;

class SettingsEvent extends ActionEvent
{
    public function __construct(
        private readonly bool $thresholdIncludesTaxes,
        private readonly bool $deductDiscounts,
    ) {
    }

    public function thresholdIncludesTaxes(): bool
    {
        return $this->thresholdIncludesTaxes;
    }

    public function deductDiscounts(): bool
    {
        return $this->deductDiscounts;
    }
}
