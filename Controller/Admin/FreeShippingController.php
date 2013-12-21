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
use FreeShipping\Form\FreeShippingRuleCreationForm;
use Propel\Runtime\Exception\PropelException;
use Thelia\Controller\Admin\AbstractCrudController;
use Thelia\Controller\Admin\unknown;
use Thelia\Form\Exception\FormValidationException;
use FreeShipping\Model\FreeShippingQuery;

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

            'admin.freeShipping',

            FreeShippingEvents::FREE_SHIPPING_RULE_CREATE,
            null,
            FreeShippingDeleteEvent::FREE_SHIPPING_RULE_DELETE,
            null,
            null
        );
    }

    public function createRuleAction(){

        $ruleCreationForm = new FreeShippingRuleCreationForm($this->getRequest());

        $message = false;

        try {

            $form = $this->validateForm($ruleCreationForm);

            $event = $this->createEventInstance($form->getData());

            $this->areaId = $form->get('area')->getData();

            if(null === $this->getExistingObject($this->areaId)){
                $this->dispatch(FreeShippingEvents::FREE_SHIPPING_RULE_CREATE, $event);
                $this->redirectSuccess($ruleCreationForm);
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
            \Thelia\Log\Tlog::getInstance()->error(sprintf("Error during free shipping rule creation process : %s.", $message));

            $ruleCreationForm->setErrorMessage($message);

            $this->getParserContext()
                ->addForm($ruleCreationForm)
                ->setGeneralError($message)
            ;
        }

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
        // TODO: Implement getCreationForm() method.
    }

    /**
     * Return the update form for this object
     */
    protected function getUpdateForm()
    {
        // TODO: Implement getUpdateForm() method.
    }

    /**
     * Hydrate the update form for this object, before passing it to the update template
     *
     * @param unknown $object
     */
    protected function hydrateObjectForm($object)
    {
        // TODO: Implement hydrateObjectForm() method.
    }

    /**
     * Creates the creation event with the provided form data
     *
     * @param unknown $formData
     */
    protected function getCreationEvent($formData)
    {
        // TODO: Implement getCreationEvent() method.
    }

    /**
     * Creates the update event with the provided form data
     *
     * @param unknown $formData
     */
    protected function getUpdateEvent($formData)
    {
        // TODO: Implement getUpdateEvent() method.
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
        // TODO: Implement eventContainsObject() method.
    }

    /**
     * Get the created object from an event.
     *
     * @param unknown $event
     */
    protected function getObjectFromEvent($event)
    {
        // TODO: Implement getObjectFromEvent() method.
    }

    /**
     * Load an existing object from the database
     */
    protected function getExistingObject()
    {
        return FreeShippingQuery::create()
            ->findOneByAreaId($this->areaId);
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

    /**
     * Render the edition template
     */
    protected function renderEditionTemplate()
    {
        // TODO: Implement renderEditionTemplate() method.
    }

    /**
     * Redirect to the edition template
     */
    protected function redirectToEditionTemplate()
    {
        // TODO: Implement redirectToEditionTemplate() method.
    }

    /**
     * Redirect to the list template
     */
    protected function redirectToListTemplate()
    {
        $this->redirect('/admin/module/FreeShipping');
    }


}