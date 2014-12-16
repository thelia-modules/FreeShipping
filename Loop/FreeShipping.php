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

namespace FreeShipping\Loop;

use FreeShipping\Model\Base\FreeShippingQuery;
use Propel\Runtime\ActiveQuery\Criteria;
use Thelia\Core\Template\Element\BaseLoop;
use Thelia\Core\Template\Element\LoopResult;
use Thelia\Core\Template\Element\LoopResultRow;
use Thelia\Core\Template\Element\PropelSearchLoopInterface;
use Thelia\Core\Template\Loop\Argument\Argument;
use Thelia\Core\Template\Loop\Argument\ArgumentCollection;
use Thelia\Model\Base\AreaQuery;
use Thelia\Type;
use Thelia\Type\TypeCollection;

/**
 *
 * FreeShipping loop
 *
 *
 * Class FreeShipping
 * @package FreeShipping\Loop
 * @author Michaël Espeche <mespeche@openstudio.fr>
 */
class FreeShipping extends BaseLoop implements PropelSearchLoopInterface
{

    /**
     * @return ArgumentCollection
     */
    protected function getArgDefinitions()
    {
        return new ArgumentCollection(
            Argument::createIntListTypeArgument('id'),
            new Argument(
                'order',
                new TypeCollection(
                    new Type\EnumListType(array('alpha', 'alpha-reverse', 'manual', 'manual_reverse', 'random', 'given_id'))
                ),
                'alpha'
            )
        );
    }

    public function buildModelCriteria()
    {

        $search = FreeShippingQuery::create();

        $id = $this->getId();

        if (!is_null($id)) {
            $search->filterById($id, Criteria::IN);
        }

        return $search;

    }

    public function parseResults(LoopResult $loopResult)
    {
        foreach ($loopResult->getResultDataCollection() as $rule) {

            $loopResultRow = new LoopResultRow($rule);

            $area = AreaQuery::create()->findOneById($rule->getAreaId());

            $loopResultRow
                ->set("ID", $rule->getId())
                ->set("AMOUNT", $rule->getAmount())
                ->set("AREA_ID", $rule->getAreaId())
                ->set("AREA_NAME", $area->getName())
            ;

            $loopResult->addRow($loopResultRow);
        }

        return $loopResult;

    }

}
