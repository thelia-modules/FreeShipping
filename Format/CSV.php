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

use FreeShipping\Format\CSVLine;

/**
 * Class CSV
 * @package FreeShipping\Format
 * @author Thelia <info@thelia.net>
 */
class CSV
{
    protected $separator;
    protected $lines=array();

    const CRLF = "\r\n";
    /**
     * @param $separator
     * @param array $lines
     */
    public function __construct($separator, array $lines=array())
    {
        $this->separator = $separator;

        foreach ($lines as $line) {
            if ($line instanceof CSVLine) {
                $this->addLine($line);
            }
        }
    }

    /**
     * @param $separator
     * @param  array $lines
     * @return CSV
     */
    public static function create($separator, array $lines=array())
    {
        return new static($separator, $lines);
    }

    /**
     * @param  CSVLine $line
     * @return $this
     */
    public function addLine(CSVLine $line)
    {
        $this->lines[] = $line;

        return $this;
    }

    /**
     * @return string parsed CSV
     */
    public function parse()
    {
        $buffer = "";

        for ($j=0; $j < ($lineslen=count($this->lines)); ++$j) {
            /** @var CSVLine $line */
            $line = $this->lines[$j];
            $aline = $line->getValues();

            for ($i=0; $i < ($linelen=count($aline)); ++$i) {
                $buffer .= "\"".$aline[$i]."\"";

                if ($i !== $linelen-1) {
                    $buffer .= $this->separator;
                }
            }
            if ($j !== $lineslen-1) {
                $buffer .= self::CRLF;
            }
        }

        return $buffer;
    }
}
