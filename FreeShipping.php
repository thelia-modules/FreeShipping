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

namespace FreeShipping;

use FreeShipping\Model\Base\FreeShippingQuery;
use Propel\Runtime\Connection\ConnectionInterface;
use Thelia\Install\Database;
use Thelia\Model\AreaQuery;
use Thelia\Model\Country;
use Thelia\Module\AbstractDeliveryModule;
use Thelia\Module\BaseModule;
use Thelia\Module\DeliveryModuleInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;


/**
 * Class FreeShipping
 * @package FreeShipping
 */
class FreeShipping extends AbstractDeliveryModule
{

    /**
     * @var
     */
    protected $request;
    /**
     * @var
     */
    protected $dispatcher;

    /**
     * @param Request $request
     */
    public function setRequest(Request $request)
    {
        $this->request = $request;
    }

    /**
     * @return mixed
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @param EventDispatcherInterface $dispatcher
     */
    public function setDispatcher(EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * @return mixed
     */
    public function getDispatcher()
    {
        return $this->dispatcher;
    }

    /**
     * @param ConnectionInterface $con
     */
    public function postActivation(ConnectionInterface $con = null)
    {
        $database = new Database($con->getWrappedConnection());
        $database->insertSql(null, array(THELIA_ROOT . '/local/modules/FreeShipping/Config/thelia.sql'));
    }


    /**
     * calculate and return delivery price
     *
     * @param Country $country
     *
     * @return mixed
     */
    public function getPostage(Country $country)
    {
        $cart = $this->getContainer()->get('request')->getSession()->getCart();

        $amount = $cart->getTotalAmount();
        $areaId = $country->getAreaId();

        $area = FreeShippingQuery::create()->findOneByAreaId($areaId);
        $maxAmount = $area->getAmount();

        if($amount >= $maxAmount){
            $postage = 0;
        }
        else{
            $area = AreaQuery::create()->findPk($areaId);

            $postage = $area->getPostage();
        }

        return $postage;

    }

    /**
     * @return string
     */
    public function getCode()
    {
        return 'FreeShipping';
    }

    /**
     * This method is called by the Delivery  loop, to check if the current module has to be displayed to the customer.
     * Override it to implements your delivery rules/
     *
     * If you return true, the delivery method will de displayed to the customer
     * If you return false, the delivery method will not be displayed
     *
     * @param Country $country the country to deliver to.
     *
     * @return boolean
     */
    public function isValidDelivery(Country $country)
    {
        return true;
    }
}
