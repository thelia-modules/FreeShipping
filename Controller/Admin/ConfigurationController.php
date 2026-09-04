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

namespace FreeShipping\Controller\Admin;

use FreeShipping\Event\FreeShippingEvents;
use FreeShipping\Event\RuleEvent;
use FreeShipping\Event\SettingsEvent;
use FreeShipping\Form\DeleteRuleForm;
use FreeShipping\Form\RuleForm;
use FreeShipping\Form\SettingsForm;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Thelia\Controller\Admin\BaseAdminController;
use Thelia\Core\Security\AccessManager;
use Thelia\Core\Security\Resource\AdminResources;
use Thelia\Core\Template\ParserContext;
use Thelia\Form\Exception\FormValidationException;

/**
 * The back office of the module: two settings and a list of rules.
 *
 * The controller reads the form and dispatches; the action saves. Nothing here
 * touches the database.
 */
#[Route('/admin/module/FreeShipping', name: 'freeshipping_config_')]
class ConfigurationController extends BaseAdminController
{
    private const CONFIGURATION_URL = '/admin/module/FreeShipping';

    #[Route('/settings', name: 'settings', methods: 'POST')]
    public function saveSettings(EventDispatcherInterface $eventDispatcher, ParserContext $parserContext): Response
    {
        if (null !== $response = $this->checkAuth(AdminResources::MODULE, [], AccessManager::UPDATE)) {
            return $response;
        }

        $form = $this->createForm(SettingsForm::getName());

        try {
            $data = $this->validateForm($form)->getData();

            $eventDispatcher->dispatch(
                new SettingsEvent(
                    (bool) ($data['threshold_includes_taxes'] ?? false),
                    (bool) ($data['deduct_discounts'] ?? false),
                ),
                FreeShippingEvents::SETTINGS_UPDATE,
            );

            return $this->generateSuccessRedirect($form) ?? $this->generateRedirect(self::CONFIGURATION_URL);
        } catch (FormValidationException $exception) {
            return $this->failed($form, $parserContext, $this->createStandardFormValidationErrorMessage($exception));
        } catch (\Exception $exception) {
            return $this->failed($form, $parserContext, $exception->getMessage());
        }
    }

    #[Route('/rule/save', name: 'rule_save', methods: 'POST')]
    public function saveRule(EventDispatcherInterface $eventDispatcher, ParserContext $parserContext): Response
    {
        if (null !== $response = $this->checkAuth(AdminResources::MODULE, [], AccessManager::UPDATE)) {
            return $response;
        }

        $form = $this->createForm(RuleForm::getName());

        try {
            $data = $this->validateForm($form)->getData();

            $id = '' === ($data['id'] ?? '') ? null : (int) $data['id'];
            $deliveryModuleId = '' === ($data['delivery_module_id'] ?? '') ? null : (int) $data['delivery_module_id'];

            $event = new RuleEvent(
                $id,
                (int) $data['area_id'],
                $deliveryModuleId,
                (float) $data['threshold'],
                $data['start_date'] ?? null,
                $data['end_date'] ?? null,
                (bool) ($data['active'] ?? false),
            );

            $eventDispatcher->dispatch($event, null === $id ? FreeShippingEvents::RULE_CREATE : FreeShippingEvents::RULE_UPDATE);

            return $this->generateSuccessRedirect($form) ?? $this->generateRedirect(self::CONFIGURATION_URL);
        } catch (FormValidationException $exception) {
            return $this->failed($form, $parserContext, $this->createStandardFormValidationErrorMessage($exception));
        } catch (\Exception $exception) {
            return $this->failed($form, $parserContext, $exception->getMessage());
        }
    }

    #[Route('/rule/delete', name: 'rule_delete', methods: 'POST')]
    public function deleteRule(EventDispatcherInterface $eventDispatcher, ParserContext $parserContext): Response
    {
        if (null !== $response = $this->checkAuth(AdminResources::MODULE, [], AccessManager::UPDATE)) {
            return $response;
        }

        $form = $this->createForm(DeleteRuleForm::getName());

        try {
            $data = $this->validateForm($form)->getData();

            $eventDispatcher->dispatch(new RuleEvent((int) $data['id']), FreeShippingEvents::RULE_DELETE);

            return $this->generateSuccessRedirect($form) ?? $this->generateRedirect(self::CONFIGURATION_URL);
        } catch (FormValidationException $exception) {
            return $this->failed($form, $parserContext, $this->createStandardFormValidationErrorMessage($exception));
        } catch (\Exception $exception) {
            return $this->failed($form, $parserContext, $exception->getMessage());
        }
    }

    private function failed(\Thelia\Form\BaseForm $form, ParserContext $parserContext, string $message): Response
    {
        $form->setErrorMessage($message);

        $parserContext
            ->addForm($form)
            ->setGeneralError($message);

        return $this->generateErrorRedirect($form) ?? $this->generateRedirect(self::CONFIGURATION_URL);
    }
}
