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

namespace FreeShipping\Hook;

use FreeShipping\Form\DeleteRuleForm;
use FreeShipping\Form\RuleForm;
use FreeShipping\Form\SettingsForm;
use FreeShipping\Repository\FreeShippingRuleRepository;
use FreeShipping\Service\FreeShippingSettings;
use FreeShipping\Service\RulePresenter;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Thelia\Core\Event\Hook\HookRenderEvent;
use Thelia\Core\Form\TheliaFormFactory;
use Thelia\Core\Hook\BaseHook;
use Thelia\Core\Template\Parser\ParserResolver;
use Thelia\Model\LangQuery;

/**
 * Renders the module configuration screen inside the back office module page.
 */
class ConfigHook extends BaseHook
{
    private const CONFIGURATION_URL = '/admin/module/FreeShipping';

    public function __construct(
        private readonly TheliaFormFactory $formFactory,
        private readonly RulePresenter $presenter,
        private readonly FreeShippingSettings $settings,
        private readonly FreeShippingRuleRepository $rules,
        ?EventDispatcherInterface $dispatcher = null,
        ?ParserResolver $parserResolver = null,
    ) {
        parent::__construct($dispatcher, $parserResolver);
    }

    public static function getSubscribedHooks(): array
    {
        return [
            'module.configuration' => [
                ['type' => 'back', 'method' => 'onModuleConfiguration'],
            ],
        ];
    }

    public function onModuleConfiguration(HookRenderEvent $event): void
    {
        $locale = $this->currentLocale();

        $settingsForm = $this->formFactory->createForm(SettingsForm::getName(), data: [
            'threshold_includes_taxes' => $this->settings->thresholdIncludesTaxes(),
            'deduct_discounts' => $this->settings->deductDiscounts(),
            'success_url' => self::CONFIGURATION_URL,
            'error_url' => self::CONFIGURATION_URL,
        ]);

        $ruleForm = $this->formFactory->createForm(RuleForm::getName(), data: $this->ruleFormData());
        $deleteForm = $this->formFactory->createForm(DeleteRuleForm::getName(), data: [
            'success_url' => self::CONFIGURATION_URL,
            'error_url' => self::CONFIGURATION_URL,
        ]);

        $event->add($this->render('FreeShipping/module_configuration.html.twig', [
            'settings_form' => $settingsForm->createView()->getView(),
            'rule_form' => $ruleForm->createView()->getView(),
            'delete_form' => $deleteForm->createView()->getView(),
            'rules' => $this->presenter->rows($locale),
            'edited_rule_id' => $this->editedRuleId(),
        ]));
    }

    /**
     * The table links to "?rule=<id>", which fills the form below it with that
     * rule instead of an empty one.
     */
    private function editedRuleId(): ?int
    {
        $id = $this->getRequest()?->query->get('rule');

        return null === $id || '' === $id ? null : (int) $id;
    }

    /**
     * @return array<string, mixed>
     */
    private function ruleFormData(): array
    {
        $data = [
            'active' => true,
            'success_url' => self::CONFIGURATION_URL,
            'error_url' => self::CONFIGURATION_URL,
        ];

        $id = $this->editedRuleId();
        $rule = null === $id ? null : $this->rules->find($id);

        if (null === $rule) {
            return $data;
        }

        $data['id'] = $rule->getId();
        $data['area_id'] = $rule->getAreaId();
        $data['delivery_module_id'] = $rule->getDeliveryModuleId() ?? '';
        $data['threshold'] = (float) $rule->getThreshold();
        $data['start_date'] = $rule->getStartDate();
        $data['end_date'] = $rule->getEndDate();
        $data['active'] = 1 === (int) $rule->getActive();

        return $data;
    }

    private function currentLocale(): string
    {
        $request = $this->getRequest();

        if (null !== $request && $request->hasSession()) {
            $locale = $request->getSession()->get('thelia.current.lang')?->getLocale();

            if (null !== $locale) {
                return $locale;
            }
        }

        return LangQuery::create()->findOneByByDefault(1)?->getLocale() ?? 'en_US';
    }
}
