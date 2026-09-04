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

namespace FreeShipping\Repository;

use FreeShipping\Model\FreeShippingRule;
use FreeShipping\Model\FreeShippingRuleQuery;

/**
 * Every read of the rule table goes through here, so a cart evaluation costs
 * one query whatever the number of carriers it has to price.
 */
final readonly class FreeShippingRuleRepository
{
    /**
     * @return FreeShippingRule[] active rules, cheapest threshold first
     */
    public function findActive(): array
    {
        return FreeShippingRuleQuery::create()
            ->filterByActive(1)
            ->orderByThreshold()
            ->find()
            ->getData();
    }

    /**
     * @return FreeShippingRule[] every rule, the way the back office lists them
     */
    public function findAll(): array
    {
        return FreeShippingRuleQuery::create()
            ->orderByPosition()
            ->orderByThreshold()
            ->find()
            ->getData();
    }

    public function find(int $id): ?FreeShippingRule
    {
        return FreeShippingRuleQuery::create()->findPk($id);
    }
}
