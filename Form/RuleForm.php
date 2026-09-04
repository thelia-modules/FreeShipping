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
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\NotBlank;
use Thelia\Form\BaseForm;
use Thelia\Model\AreaQuery;
use Thelia\Model\ModuleQuery;
use Thelia\Module\BaseModule;

/**
 * One free shipping rule: which area, which carrier, above which amount, and
 * for how long.
 */
class RuleForm extends BaseForm
{
    protected function buildForm(): void
    {
        $this->formBuilder
            ->add('id', HiddenType::class, ['required' => false])
            ->add('area_id', ChoiceType::class, [
                'label' => $this->translator->trans('Delivery area', [], FreeShipping::DOMAIN_NAME),
                'choices' => $this->areaChoices(),
                'required' => true,
                'constraints' => [new NotBlank()],
            ])
            ->add('delivery_module_id', ChoiceType::class, [
                'label' => $this->translator->trans('Carrier', [], FreeShipping::DOMAIN_NAME),
                'choices' => $this->carrierChoices(),
                'required' => false,
                'placeholder' => false,
            ])
            ->add('threshold', NumberType::class, [
                'label' => $this->translator->trans('Threshold', [], FreeShipping::DOMAIN_NAME),
                'scale' => 2,
                'required' => true,
                'constraints' => [new NotBlank(), new GreaterThanOrEqual(0)],
            ])
            ->add('start_date', DateType::class, [
                'label' => $this->translator->trans('Start date', [], FreeShipping::DOMAIN_NAME),
                'widget' => 'single_text',
                'required' => false,
            ])
            ->add('end_date', DateType::class, [
                'label' => $this->translator->trans('End date', [], FreeShipping::DOMAIN_NAME),
                'widget' => 'single_text',
                'required' => false,
            ])
            ->add('active', CheckboxType::class, [
                'label' => $this->translator->trans('Active', [], FreeShipping::DOMAIN_NAME),
                'required' => false,
            ])
        ;
    }

    /**
     * @return array<string, int>
     */
    private function areaChoices(): array
    {
        $choices = [];

        foreach (AreaQuery::create()->orderByName()->find() as $area) {
            $choices[(string) $area->getName()] = $area->getId();
        }

        return $choices;
    }

    /**
     * @return array<string, ?int>
     */
    private function carrierChoices(): array
    {
        $choices = [$this->translator->trans('Every carrier', [], FreeShipping::DOMAIN_NAME) => ''];

        $modules = ModuleQuery::create()
            ->filterByType(BaseModule::DELIVERY_MODULE_TYPE)
            ->filterByActivate(1)
            ->orderByPosition()
            ->find();

        // The back office edits in its own language, which the request carries
        // on /admin: a carrier untranslated there falls back to its code.
        $locale = $this->request->getLocale();

        foreach ($modules as $module) {
            $choices[(string) ($module->setLocale($locale)->getTitle() ?: $module->getCode())] = $module->getId();
        }

        return $choices;
    }
}
