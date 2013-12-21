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

namespace FreeShipping\Event;

/**
 * Class FreeShippingDeleteEvent
 *
 * @package FreeShipping\Event
 * @author Michaël Espeche <mespeche@openstudio.fr>
 */
class FreeShippingDeleteEvent extends FreeShippingEvents
{

    const FREE_SHIPPING_RULE_DELETE = 'freeShipping.action.rule.delete';

    /**
     * @var int free shipping id
     */
    protected $freeShippingId;

    /**
     * @param int $freeShippingId
     */
    public function __construct($freeShippingId)
    {
        $this->freeShippingId = $freeShippingId;
    }

    /**
     * @param int $freeShippingId
     */
    public function setFreeShippingId($freeShippingId)
    {
        $this->freeShippingId = $freeShippingId;

        return $this;
    }

    /**
     * @return int
     */
    public function getFreeShippingId()
    {
        return $this->freeShippingId;
    }

}
