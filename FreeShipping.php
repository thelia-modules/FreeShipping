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
use Thelia\Core\Translation\Translator;
use Thelia\Install\Database;
use Thelia\Model\AreaQuery;
use Thelia\Model\Country;
use Thelia\Model\Lang;
use Thelia\Model\LangQuery;
use Thelia\Model\Message;
use Thelia\Model\MessageQuery;
use Thelia\Model\ModuleQuery;
use Thelia\Module\AbstractDeliveryModule;

/**
 * Class FreeShipping
 * @package FreeShipping
 */
class FreeShipping extends AbstractDeliveryModule
{
    /** The module domain for internationalisation */
    const MODULE_DOMAIN = "freeshipping";

    /**
     * The confirmation message identifier
     */
    const MESSAGE_SEND_CONFIRMATION = "send_comfirmation_freeshipping";

    /** @var Translator $translator */
    protected $translator;

    protected function trans($id, $locale, $parameters = [])
    {
        if ($this->translator === null) {
            $this->translator = Translator::getInstance();
        }

        return $this->translator->trans($id, $parameters, self::MODULE_DOMAIN, $locale);
    }

    /**
     * @param ConnectionInterface $con
     */
    public function postActivation(ConnectionInterface $con = null)
    {
        $database = new Database($con->getWrappedConnection());
        $database->insertSql(null, [__DIR__ . DS . 'Config' . DS . 'thelia.sql']);


        $languages = LangQuery::create()->find();

        if (null === MessageQuery::create()->findOneByName(self::MESSAGE_SEND_CONFIRMATION)) {
            $message = new Message();
            $message
                ->setName(self::MESSAGE_SEND_CONFIRMATION)
                ->setHtmlLayoutFileName('')
                ->setHtmlTemplateFileName(self::MESSAGE_SEND_CONFIRMATION.'.html')
                ->setTextLayoutFileName('')
                ->setTextTemplateFileName(self::MESSAGE_SEND_CONFIRMATION.'.txt')
            ;

            foreach ($languages as $language) {
                /** @var Lang $language */
                $locale = $language->getLocale();

                $message->setLocale($locale);

                $message->setTitle(
                    $this->trans('Order send confirmation', $locale)
                );

                $message->setSubject(
                    $this->trans('Order send confirmation', $locale)
                );
            }

            $message->save();
        }
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
