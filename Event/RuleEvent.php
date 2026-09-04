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

use FreeShipping\Model\FreeShippingRule;
use Thelia\Core\Event\ActionEvent;

/**
 * Carries one rule from the back office to the action that saves it. The saved
 * rule is put back on the event so the controller can report on it.
 */
class RuleEvent extends ActionEvent
{
    private ?FreeShippingRule $rule = null;

    public function __construct(
        private readonly ?int $id,
        private readonly int $areaId = 0,
        private readonly ?int $deliveryModuleId = null,
        private readonly float $threshold = 0.0,
        private readonly ?\DateTimeInterface $startDate = null,
        private readonly ?\DateTimeInterface $endDate = null,
        private readonly bool $active = true,
        private readonly ?int $position = null,
    ) {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAreaId(): int
    {
        return $this->areaId;
    }

    public function getDeliveryModuleId(): ?int
    {
        return $this->deliveryModuleId;
    }

    public function getThreshold(): float
    {
        return $this->threshold;
    }

    public function getStartDate(): ?\DateTimeInterface
    {
        return $this->startDate;
    }

    public function getEndDate(): ?\DateTimeInterface
    {
        return $this->endDate;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function getPosition(): ?int
    {
        return $this->position;
    }

    public function getRule(): ?FreeShippingRule
    {
        return $this->rule;
    }

    public function setRule(?FreeShippingRule $rule): self
    {
        $this->rule = $rule;

        return $this;
    }
}
