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

namespace FreeShipping\Tests\Unit;

use FreeShipping\Form\RuleForm;
use PHPUnit\Framework\TestCase;

/**
 * The back office asks for days, not for instants, and both bounds of a period
 * are included: a rule that ends on the last day of a sale has to still apply
 * on that day.
 */
final class RulePeriodBoundsTest extends TestCase
{
    public function testAStartDayBeginsAtItsFirstSecond(): void
    {
        self::assertSame('2026-12-01 00:00:00', RuleForm::toDate('2026-12-01')?->format('Y-m-d H:i:s'));
    }

    public function testAnEndDayRunsUntilItsLastSecond(): void
    {
        self::assertSame('2026-12-31 23:59:59', RuleForm::toEndOfDay('2026-12-31')?->format('Y-m-d H:i:s'));
    }

    public function testAnEmptyBoundIsNoBound(): void
    {
        self::assertNull(RuleForm::toDate(''));
        self::assertNull(RuleForm::toDate(null));
        self::assertNull(RuleForm::toEndOfDay(''));
        self::assertNull(RuleForm::toEndOfDay(null));
    }

    public function testAnInstantGivenExplicitlyIsKeptAsIs(): void
    {
        $instant = new \DateTimeImmutable('2026-12-31 08:30:00');

        self::assertSame('2026-12-31 08:30:00', RuleForm::toEndOfDay($instant)?->format('Y-m-d H:i:s'));
    }
}
