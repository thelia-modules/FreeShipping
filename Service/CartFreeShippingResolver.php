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

use Thelia\Domain\Cart\CartFacade;
use Thelia\Model\Address;
use Thelia\Model\Cart;
use Thelia\Model\CartAddress;
use Thelia\Model\Country;
use Thelia\Model\State;

/**
 * Answers the free shipping decision for the cart of the current session, which
 * is the only cart the front is ever allowed to read.
 *
 * The country comes from the delivery address the checkout already knows, then
 * from the default address of the customer signed in. With neither, the
 * decision is taken with no country at all, and the cart announces the lowest
 * threshold of the shop.
 */
final class CartFreeShippingResolver
{
    /**
     * The cart page asks twice: once to know whether to render the message at
     * all, once to render it. The answer cannot change inside a request.
     */
    private ?FreeShippingDecision $decision = null;

    public function __construct(
        private readonly CartFacade $cartFacade,
        private readonly FreeShippingEvaluator $evaluator,
    ) {
    }

    public function resolve(): FreeShippingDecision
    {
        return $this->decision ??= $this->compute();
    }

    private function compute(): FreeShippingDecision
    {
        $cart = $this->cartFacade->getCartFromSession();

        // An empty cart has nothing to announce: the page already says so.
        if (!$cart instanceof Cart || 0 === $cart->countCartItems()) {
            return FreeShippingDecision::none(0.0);
        }

        [$country, $state] = $this->destinationOf($cart);

        return $this->evaluator->evaluate($cart, $country, $state, null);
    }

    /**
     * @return array{0: ?Country, 1: ?State}
     */
    private function destinationOf(Cart $cart): array
    {
        $cartAddress = $cart->getCartAddressRelatedByAddressDeliveryId();

        if ($cartAddress instanceof CartAddress) {
            return [$cartAddress->getCountry(), $cartAddress->getState()];
        }

        $address = $cart->getCustomer()?->getDefaultAddress();

        if ($address instanceof Address) {
            return [$address->getCountry(), $address->getState()];
        }

        return [null, null];
    }
}
