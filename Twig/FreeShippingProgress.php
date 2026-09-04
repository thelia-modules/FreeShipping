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

namespace FreeShipping\Twig;

use FreeShipping\FreeShipping;
use FreeShipping\Service\CartFreeShippingResolver;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveListener;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;
use Thelia\Core\Translation\Translator;
use Thelia\Domain\Cart\CartFacade;
use Thelia\Model\Cart;
use Thelia\Model\Currency;

/**
 * The cart message: what is left to add to be shipped for free, or the fact
 * that shipping is already offered.
 *
 * The cart page updates itself without reloading, and the hook point this
 * component sits on is outside the cart component, so it re-renders on the
 * browser side events the cart emits when a line changes.
 */
#[AsLiveComponent(name: 'FreeShippingProgress', template: '@FreeShippingModule/components/FreeShippingProgress.html.twig')]
final class FreeShippingProgress
{
    use DefaultActionTrait;

    public function __construct(
        private readonly CartFreeShippingResolver $resolver,
        private readonly CartFacade $cartFacade,
        private readonly RequestStack $requestStack,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * The cart page emits these when a quantity changes, a line goes away, or a
     * promotion code is applied. Their names are the ones the shipped front
     * office theme uses; a theme that emits none of them keeps a message that
     * only follows a page reload.
     */
    #[LiveListener('UPDATE_ITEM_QUANTITY_EVENT')]
    #[LiveListener('CART_DELETE_ITEM_EVENT')]
    #[LiveListener('CART_ADD_ITEM_EVENT')]
    #[LiveListener('syncSummary')]
    public function refresh(): void
    {
        // Re-rendering is the whole point: the message is computed on render.
    }

    /**
     * The finished sentence, or an empty string when there is nothing to say.
     */
    #[ExposeInTemplate]
    public function getMessage(): string
    {
        try {
            $decision = $this->resolver->resolve();

            if (null === $decision->getThreshold()) {
                return '';
            }

            if ($decision->isFree()) {
                return $this->trans('Your delivery is free');
            }

            $remaining = $decision->getRemainingAmount();

            if (null === $remaining) {
                return '';
            }

            return $this->trans('Only %amount% more for free delivery', ['%amount%' => $this->formatAmount($remaining)]);
        } catch (\Throwable $throwable) {
            $this->logger->error('The free shipping cart message could not be computed: '.$throwable->getMessage(), ['exception' => $throwable]);

            return '';
        }
    }

    private function trans(string $id, array $parameters = []): string
    {
        return Translator::getInstance()->trans($id, $parameters, FreeShipping::DOMAIN_NAME, $this->locale());
    }

    private function formatAmount(float $amount): string
    {
        $formatter = new \NumberFormatter($this->locale(), \NumberFormatter::CURRENCY);

        return $formatter->formatCurrency($amount, $this->currencyCode()) ?: (string) $amount;
    }

    private function locale(): string
    {
        $request = $this->requestStack->getCurrentRequest();

        return null !== $request ? $request->getLocale() : 'en_US';
    }

    private function currencyCode(): string
    {
        $cart = $this->cartFacade->getCartFromSession();
        $currency = $cart instanceof Cart ? $cart->getCurrency() : null;

        if (!$currency instanceof Currency) {
            $currency = Currency::getDefaultCurrency();
        }

        return $currency->getCode() ?? 'EUR';
    }
}
