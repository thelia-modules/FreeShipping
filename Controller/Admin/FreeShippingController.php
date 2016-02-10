<?php
/*************************************************************************************/
/*                                                                                   */
/*      Thelia                                                                       */
/*                                                                                   */
/*      Copyright (c) OpenStudio                                                     */
/*      email : info@thelia.net                                                      */
/*      web : http://www.thelia.net                                                  */
/*                                                                                   */
/*      This program is free software; you can redistribute it and/or modify         */
/*      it under the terms of the GNU General Public License as published by         */
/*      the Free Software Foundation; either version 3 of the License                */
/*                                                                                   */
/*      This program is distributed in the hope that it will be useful,              */
/*      but WITHOUT ANY WARRANTY; without even the implied warranty of               */
/*      MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the                */
/*      GNU General Public License for more details.                                 */
/*                                                                                   */
/*      You should have received a copy of the GNU General Public License            */
/*      along with this program. If not, see <http://www.gnu.org/licenses/>.         */
/*                                                                                   */
/*************************************************************************************/

namespace FreeShipping\Controller\Admin;

use FreeShipping\Event\FreeShippingDeleteEvent;
use FreeShipping\Event\FreeShippingEvents;
use FreeShipping\Event\FreeShippingUpdateEvent;
use FreeShipping\Form\FreeShippingRuleCreationForm;
use FreeShipping\Form\FreeShippingRuleModificationForm;
use FreeShipping\Model\FreeShippingQuery;
use Propel\Runtime\Exception\PropelException;
use Thelia\Controller\Admin\AbstractCrudController;
use Thelia\Controller\Admin\unknown;
use Thelia\Core\Security\AccessManager;
use Thelia\Core\Security\Resource\AdminResources;
use Thelia\Form\Exception\FormValidationException;

/**
 * Class FreeShippingController
 * @package FreeShipping\Controller\Admin
 * @author Michaël Espeche <mespeche@openstudio.fr>
 */
class FreeShippingController extends AbstractCrudController
{

    public $areaId;

    public function __construct()
    {
        parent::__construct(
            'freeShipping',
            'manual',
            'freeShipping_order',
            AdminResources::MODULE,
            FreeShippingEvents::FREE_SHIPPING_RULE_CREATE,
            FreeShippingUpdateEvent::FREE_SHIPPING_RULE_UPDATE,
            FreeShippingDeleteEvent::FREE_SHIPPING_RULE_DELETE,
            null,
            null,
            'FreeShipping'
        );
    }

    public function createRuleAction()
    {

        if (null !== $response = $this->checkAuth(array(), array('FreeShipping'), AccessManager::CREATE)) {
            return $response;
        }

        $ruleCreationForm = new FreeShippingRuleCreationForm($this->getRequest());

        $message = false;

        try {

            $form = $this->validateForm($ruleCreationForm);

            $event = $this->createEventInstance($form->getData());

            $this->areaId = $form->get('area')->getData();

            if(null === $this->getExistingObject()){
                $this->dispatch(FreeShippingEvents::FREE_SHIPPING_RULE_CREATE, $event);
                return $this->generateSuccessRedirect($ruleCreationForm);
            }
            else{
                throw new \Exception("A rule with this area already exist");
            }

        } catch (FormValidationException $e) {
            $message = sprintf("Please check your input: %s", $e->getMessage());
        } catch (PropelException $e) {
            $message = $e->getMessage();
        } catch (\Exception $e) {
            $message = sprintf("Sorry, an error occured: %s", $e->getMessage()." ".$e->getFile());
        }

        if ($message !== false) {
            \Thelia\Log\Tlog::getInstance()->error(
                sprintf("Error during free shipping rule creation process : %s.", $message)
            );

            $ruleCreationForm->setErrorMessage($message);

            $this->getParserContext()
                ->addForm($ruleCreationForm)
                ->setGeneralError($message)
            ;
        }

        // Redirect
        return $this->generateRedirectFromRoute(
            'admin.module.configure', array(), array('module_code' => 'FreeShipping')
        );

    }

    /**
     * @param $data
     * @return \FreeShipping\Event\FreeShippingEvents
     */
    private function createEventInstance($data)
    {

        $freeShippingEvent = new FreeShippingEvents(
            $data['amount'],
            $data['area']
        );

        return $freeShippingEvent;
    }

    /**
     * Return the creation form for this object
     */
    protected function getCreationForm()
    {
        return new FreeShippingRuleCreationForm($this->getRequest());
    }

    /**
     * Return the update form for this object
     */
    protected function getUpdateForm()
    {
        return new FreeShippingRuleModificationForm($this->getRequest());
    }

    /**
     * Hydrate the update form for this object, before passing it to the update template
     *
     * @param unknown $object
     */
    protected function hydrateObjectForm($object)
    {
        // Prepare the data that will hydrate the form
        $data = array(
            'id' => $object->getId(),
            'area' => $object->getAreaId(),
            'amount' => $object->getAmount()
        );

        // Setup the object form
        return new FreeShippingRuleModificationForm($this->getRequest(), "form", $data);
    }

    /**
     * Creates the creation event with the provided form data
     *
     * @param unknown $formData
     */
    protected function getCreationEvent($formData)
    {
        return new FreeShippingEvents($formData['amount'], $formData['area']);
    }

    /**
     * Creates the update event with the provided form data
     *
     * @param unknown $formData
     */
    protected function getUpdateEvent($formData)
    {
        $freeShippingUpdateEvent = new FreeShippingUpdateEvent($formData['id']);

        $freeShippingUpdateEvent
            ->setArea($formData['area'])
            ->setAmount($formData['amount']);

        return $freeShippingUpdateEvent;
    }

    /**
     * Creates the delete event with the provided form data
     */
    protected function getDeleteEvent()
    {
        return new FreeShippingDeleteEvent($this->getRequest()->get('rule_id'));
    }

    /**
     * Return true if the event contains the object, e.g. the action has updated the object in the event.
     *
     * @param unknown $event
     */
    protected function eventContainsObject($event)
    {
        return $event->hasRule();
    }

    /**
     * Get the created object from an event.
     *
     * @param unknown $event
     */
    protected function getObjectFromEvent($event)
    {
        return $event->getRule();
    }

    /**
     * Load an existing object from the database
     */
    protected function getExistingObject()
    {
        if(null !== $this->areaId){
            return FreeShippingQuery::create()
                ->findOneByAreaId($this->areaId);
        } else {
            return FreeShippingQuery::create()
                ->findOneById($this->getRequest()->get('ruleId', 0));
        }
    }

    /**
     * Returns the object label form the object event (name, title, etc.)
     *
     * @param unknown $object
     */
    protected function getObjectLabel($object)
    {
        // TODO: Implement getObjectLabel() method.
    }

    /**
     * Returns the object ID from the object
     *
     * @param unknown $object
     */
    protected function getObjectId($object)
    {
        // TODO: Implement getObjectId() method.
    }

    /**
     * Render the main list template
     *
     * @param unknown $currentOrder , if any, null otherwise.
     */
    protected function renderListTemplate($currentOrder)
    {
        // TODO: Implement renderListTemplate() method.
    }

    protected function getEditionArguments()
    {
        return array(
            'ruleId' => $this->getRequest()->get('ruleId', 0)
        );
    }

    /**
     * Render the edition template
     */
    protected function renderEditionTemplate()
    {
        return $this->render('rule-edit', $this->getEditionArguments());
    }

    /**
     * Redirect to the edition template
     */
    protected function redirectToEditionTemplate()
    {
        $args = $this->getEditionArguments();
        return $this->generateRedirectFromRoute("admin.freeShipping.rule.edit", [], ["ruleId" => $args['ruleId']]);
    }

    /**
     * Redirect to the list template
     */
    protected function redirectToListTemplate()
    {
        return $this->generateRedirectFromRoute("admin.module.configure", [], ["module_code" => "FreeShipping"]);
    }

    protected function performAdditionalUpdateAction($updateEvent)
    {
        return $this->redirectToListTemplate();
    }


}
