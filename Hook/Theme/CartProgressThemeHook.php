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

namespace FreeShipping\Hook\Theme;

use FreeShipping\Service\CartFreeShippingResolver;
use Psr\Log\LoggerInterface;
use Thelia\Core\Hook\Theme\ThemeHookInterface;
use Twig\Environment;

/**
 * Puts the free shipping message at the top of the cart page.
 *
 * Nothing is rendered when no rule applies to this cart, so a shop without free
 * shipping keeps the cart page it had. When a rule does apply, a live component
 * is rendered instead of plain markup: the message has to follow the cart as
 * quantities change, and the cart page updates itself without reloading.
 */
final readonly class CartProgressThemeHook implements ThemeHookInterface
{
    public function __construct(
        private Environment $twig,
        private CartFreeShippingResolver $resolver,
        private LoggerInterface $logger,
    ) {
    }

    public function supports(string $hookName): bool
    {
        return 'cart.top' === $hookName;
    }

    public function render(string $hookName, array $parameters): string
    {
        try {
            if (null === $this->resolver->resolve()->getThreshold()) {
                return '';
            }

            return $this->twig->render('@FreeShippingModule/theme-hook/free_shipping_progress.html.twig');
        } catch (\Throwable $throwable) {
            $this->logger->error('The free shipping cart message could not be rendered: '.$throwable->getMessage(), ['exception' => $throwable]);

            return '';
        }
    }
}
