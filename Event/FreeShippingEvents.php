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

/**
 * The back office never writes a rule itself: it dispatches, an action saves.
 */
final class FreeShippingEvents
{
    public const RULE_CREATE = 'freeshipping.rule.create';
    public const RULE_UPDATE = 'freeshipping.rule.update';
    public const RULE_DELETE = 'freeshipping.rule.delete';
    public const SETTINGS_UPDATE = 'freeshipping.settings.update';
}
