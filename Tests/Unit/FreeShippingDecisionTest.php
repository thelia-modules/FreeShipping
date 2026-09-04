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

use FreeShipping\Service\FreeShippingDecision;
use PHPUnit\Framework\TestCase;

/**
 * The decision is what both the checkout and the cart message read, so the
 * amount it says is left to reach has to be right to the cent.
 */
final class FreeShippingDecisionTest extends TestCase
{
    public function testAnEmptyDecisionOffersNothingAndAnnouncesNothing(): void
    {
        $decision = FreeShippingDecision::none(45.6);

        self::assertFalse($decision->isFree());
        self::assertNull($decision->getThreshold());
        self::assertNull($decision->getRemainingAmount());
        self::assertSame(45.6, $decision->getComparedAmount());
    }

    public function testAPendingDecisionAnnouncesWhatIsLeftToReach(): void
    {
        $decision = FreeShippingDecision::pending(null, 50.0, 45.6);

        self::assertFalse($decision->isFree());
        self::assertSame(50.0, $decision->getThreshold());
        self::assertSame(4.4, $decision->getRemainingAmount());
    }

    public function testTheRemainingAmountIsRoundedToTheCent(): void
    {
        $decision = FreeShippingDecision::pending(null, 50.0, 22.803);

        self::assertSame(27.2, $decision->getRemainingAmount());
    }

    public function testAGrantedDecisionHasNothingLeftToReach(): void
    {
        $decision = FreeShippingDecision::granted(null, 50.0, 68.4);

        self::assertTrue($decision->isFree());
        self::assertSame(50.0, $decision->getThreshold());
        self::assertNull($decision->getRemainingAmount());
    }
}
