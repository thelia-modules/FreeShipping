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

use Thelia\Core\Event\ActionEvent;

/**
 *
 * This class contains all FreeShipping events identifiers used by FreeShipping Core
 *
 * @author Michaël Espeche <mespeche@openstudio.fr>
 */

class FreeShippingEvents extends ActionEvent
{

    const FREE_SHIPPING_RULE_CREATE = 'freeShipping.action.rule.create';

    /**
     * @var
     */
    protected $area;
    /**
     * @var
     */
    protected $amount;
    /**
     * @var
     */
    protected $rule;

    /**
     * @param $amount
     * @param $area
     */
    public function __construct($amount, $area)
    {
        $this->amount = $amount;
        $this->area = $area;
    }


    /**
     * @param mixed $amount
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * @param mixed $area
     */
    public function setArea($area)
    {
        $this->area = $area;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getArea()
    {
        return $this->area;
    }

    /**
     * @param mixed $rule
     */
    public function setRule($rule)
    {
        $this->rule = $rule;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getRule()
    {
        return $this->rule;
    }

    /**
     * check if rule exists
     *
     * @return bool
     */
    public function hasRule()
    {
        return null !== $this->rule;
    }

}