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

/**
 * Class FreeShipping
 * @package FreeShipping
 */
class FreeShipping extends AbstractDeliveryModule
{
    /**
     * @param ConnectionInterface $con
     */
    public function postActivation(ConnectionInterface $con = null)
    {
        $database = new Database($con->getWrappedConnection());
        $database->insertSql(null, [__DIR__ . DS . 'Config' . DS . 'thelia.sql']);
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
        return 0;
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
        $cart = $this->getRequest()->getSession()->getSessionCart($this->getDispatcher());

        $amount = $cart->getTaxedAmount($country);
        $areaId = $country->getAreaId();

        $area = FreeShippingQuery::create()->findOneByAreaId($areaId);
        if (isset($area)) {
            $maxAmount = $area->getAmount();

            if ($amount >= $maxAmount) {
                return true;
            }
        }

        return false;
    }
}
