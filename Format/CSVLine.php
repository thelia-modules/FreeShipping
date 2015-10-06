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

namespace FreeShipping\Format;

/**
 * Class CSVLine
 * @package FreeShipping\Format
 * @author Thelia <info@thelia.net>
 */
class CSVLine
{
    protected $values=array();

    public function __construct(array $values)
    {
        $this->values = $values;
    }

    /**
     * @param  array   $values
     * @return CSVLine
     */
    public static function create(array $values)
    {
        return new static($values);
    }

    /**
     * @return array
     */
    public function getValues()
    {
        return $this->values;
    }

    /**
     * @param $value
     * @return $this
     */
    public function addValue($value)
    {
        $this->values[] = $value;

        return $this;
    }
}
