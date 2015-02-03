<?php
/*************************************************************************************/
/*                                                                                   */
/*      Thelia	                                                                     */
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
/*	    along with this program. If not, see <http://www.gnu.org/licenses/>.         */
/*                                                                                   */
/*************************************************************************************/

namespace FreeShipping\Action;

use FreeShipping\Event\FreeShippingDeleteEvent;
use FreeShipping\Event\FreeShippingEvents;
use FreeShipping\Event\FreeShippingUpdateEvent;
use FreeShipping\Model\FreeShipping;
use FreeShipping\Model\FreeShippingQuery;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Thelia\Action\BaseAction;
use Thelia\Model\Base\AreaQuery;

/**
 *
 * FreeShippingAction class where all actions are managed
 *
 * Class FreeShippingAction
 * @package FreeShipping\Action
 * @author Michaël Espeche <mespeche@openstudio.fr>
 */
class FreeShippingAction extends BaseAction implements EventSubscriberInterface
{


    public function createRule(FreeShippingEvents $event)
    {
        $rule = new FreeShipping();

        $freeShippingArea = FreeShippingQuery::create()->findOneByAreaId($event->getArea());

        if (null === $freeShippingArea) {
            $rule
                ->setAmount($event->getAmount())
                ->setAreaId($event->getArea())
                ->save();
        } else {
            $area = AreaQuery::create()->findOneById($event->getArea());

            throw new \Exception(sprintf("A free shipping rule already exists for the '%s' area", $area->getName()));
        }

    }

    public function updateRule(FreeShippingUpdateEvent $event)
    {

        $areaId = $event->getArea();
        $freeShippingArea = FreeShippingQuery::create()->findOneByAreaId($areaId);

        if (null === $freeShippingArea || $freeShippingArea->getAmount() !== $event->getAmount() ) {

            $id = $event->getRuleId();

            if (null !== $freeShipping = FreeShippingQuery::create()->findPk($id)) {

                $freeShipping->setDispatcher($event->getDispatcher());

                $freeShipping
                    ->setAreaId($event->getArea())
                    ->setAmount($event->getAmount())
                    ->save();

                $event->setRule($freeShipping);
            }

        } else {
            $area = AreaQuery::create()->findOneById($areaId);

            throw new \Exception(sprintf("A free shipping rule already exists for the '%s' area", $area->getName()));
        }


    }

    public function deleteRule(FreeShippingDeleteEvent $event)
    {

        $id = $event->getFreeShippingId();

        if (null !== $freeShipping = FreeShippingQuery::create()->findPk($id)) {

            $freeShipping->setDispatcher($event->getDispatcher())
                ->delete();

        }
    }


    /**
     * Returns an array of event names this subscriber wants to listen to.
     *
     * The array keys are event names and the value can be:
     *
     *  * The method name to call (priority defaults to 0)
     *  * An array composed of the method name to call and the priority
     *  * An array of arrays composed of the method names to call and respective
     *    priorities, or 0 if unset
     *
     * For instance:
     *
     *  * array('eventName' => 'methodName')
     *  * array('eventName' => array('methodName', $priority))
     *  * array('eventName' => array(array('methodName1', $priority), array('methodName2'))
     *
     * @return array The event names to listen to
     *
     * @api
     */
    public static function getSubscribedEvents()
    {
        return array(
            FreeShippingEvents::FREE_SHIPPING_RULE_CREATE      => array('createRule', 128),
            FreeShippingUpdateEvent::FREE_SHIPPING_RULE_UPDATE => array('updateRule', 128),
            FreeShippingDeleteEvent::FREE_SHIPPING_RULE_DELETE => array('deleteRule', 128)
        );
    }
}
