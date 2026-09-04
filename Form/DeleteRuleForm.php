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

use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Validator\Constraints\NotBlank;
use Thelia\Form\BaseForm;

/**
 * Deleting a rule is a form of its own so the cross site request forgery token
 * is checked the same way it is on every other write of the back office.
 */
class DeleteRuleForm extends BaseForm
{
    protected function buildForm(): void
    {
        $this->formBuilder->add('id', HiddenType::class, [
            'required' => true,
            'constraints' => [new NotBlank()],
        ]);
    }
}
