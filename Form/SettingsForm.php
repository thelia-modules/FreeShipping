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

namespace FreeShipping\Form;

use FreeShipping\FreeShipping;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Thelia\Form\BaseForm;

/**
 * The two settings that decide which cart total the threshold is compared with.
 *
 * The sentence that explains each of them is rendered by the template, not
 * passed as a `help` option: the back office form theme drops help on a
 * checkbox, and these two settings are checkboxes.
 */
class SettingsForm extends BaseForm
{
    protected function buildForm(): void
    {
        $this->formBuilder
            ->add('threshold_includes_taxes', CheckboxType::class, [
                'label' => $this->translator->trans('Compare the threshold with the tax included total', [], FreeShipping::DOMAIN_NAME),
                'required' => false,
            ])
            ->add('deduct_discounts', CheckboxType::class, [
                'label' => $this->translator->trans('Subtract discounts before comparing', [], FreeShipping::DOMAIN_NAME),
                'required' => false,
            ])
        ;
    }
}
