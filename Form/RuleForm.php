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
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
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
    public const DATE_FORMAT = 'Y-m-d';

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
            // Dates are kept as strings on purpose: the parser context drops
            // objects from the data it stores to redisplay a refused form, so a
            // DateTime field would come back empty after a validation error.
            ->add('start_date', DateType::class, [
                'label' => $this->translator->trans('Start date', [], FreeShipping::DOMAIN_NAME),
                'widget' => 'single_text',
                'input' => 'string',
                'input_format' => self::DATE_FORMAT,
                'required' => false,
            ])
            ->add('end_date', DateType::class, [
                'label' => $this->translator->trans('End date', [], FreeShipping::DOMAIN_NAME),
                'widget' => 'single_text',
                'input' => 'string',
                'input_format' => self::DATE_FORMAT,
                'required' => false,
                'constraints' => [
                    new Callback($this->checkPeriodOrder(...)),
                ],
            ])
            ->add('active', CheckboxType::class, [
                'label' => $this->translator->trans('Active', [], FreeShipping::DOMAIN_NAME),
                'required' => false,
            ])
        ;
    }

    /**
     * A period that ends before it starts would save a rule that can never
     * apply, and the table would still show it as active.
     */
    public function checkPeriodOrder(mixed $value, ExecutionContextInterface $context): void
    {
        $endDate = self::toDate($value);
        $startDate = self::toDate($context->getRoot()->get('start_date')->getData());

        if (null === $endDate || null === $startDate) {
            return;
        }

        if ($endDate < $startDate) {
            $context->addViolation(
                $this->translator->trans('The end date must not come before the start date.', [], FreeShipping::DOMAIN_NAME),
            );
        }
    }

    /**
     * The two date fields answer with a "Y-m-d" string, or nothing at all.
     */
    public static function toDate(mixed $value): ?\DateTimeImmutable
    {
        if ($value instanceof \DateTimeInterface) {
            return \DateTimeImmutable::createFromInterface($value);
        }

        if (!\is_string($value) || '' === trim($value)) {
            return null;
        }

        return \DateTimeImmutable::createFromFormat('!'.self::DATE_FORMAT, $value) ?: null;
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
