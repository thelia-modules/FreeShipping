<?php
/*************************************************************************************/
/*      This file is part of the Thelia package.                                     */
/*                                                                                   */
/*      Copyright (c) OpenStudio                                                     */
/*      email : dev@thelia.net                                                       */
/*      web : http://www.thelia.net                                                  */
/*                                                                                   */
/*      For the full copyright and license information, please view the LICENSE.txt  */
/*      file that was distributed with this source code.                             */
/*************************************************************************************/
/*************************************************************************************/
namespace FreeShipping\EventListener;

use FreeShipping\FreeShipping;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Thelia\Action\BaseAction;
use Thelia\Core\Event\Order\OrderEvent;
use Thelia\Core\Event\TheliaEvents;
use Thelia\Core\Template\ParserInterface;
use Thelia\Mailer\MailerFactory;
use Thelia\Model\ConfigQuery;
use Thelia\Model\MessageQuery;

/**
 * Class SendConfirmationEmail
 */
class SendConfirmationEmail extends BaseAction implements EventSubscriberInterface
{
    /**
     * @var MailerFactory
     */
    protected $mailer;
    /**
     * @var ParserInterface
     */
    protected $parser;

    public function __construct(ParserInterface $parser, MailerFactory $mailer)
    {
        $this->parser = $parser;
        $this->mailer = $mailer;
    }

    /*
    * @params OrderEvent $order
    * Checks if order delivery module is icirelais and if order new status is sent, send an email to the customer.
    */
    public function update_status(OrderEvent $event)
    {
        if ($event->getOrder()->getDeliveryModuleId() === FreeShipping::getModuleId()) {
            if ($event->getOrder()->isSent()) {
                $contact_email = ConfigQuery::getStoreEmail();

                if ($contact_email) {
                    $order = $event->getOrder();
                    $customer = $order->getCustomer();

                    $this->mailer->sendEmailToCustomer(
                        FreeShipping::MESSAGE_SEND_CONFIRMATION,
                        $order->getCustomer(),
                        [
                            'order_id' => $order->getId(),
                            'order_ref' => $order->getRef(),
                            'customer_id' => $customer->getId(),
                            'order_date' => $order->getCreatedAt(),
                            'update_date' => $order->getUpdatedAt(),
                            'package' => $order->getDeliveryRef()
                        ]
                    );
                }
            }
        }
    }


    /**
     *
     * @inheritdoc
     */
    public static function getSubscribedEvents()
    {
        return array(
            TheliaEvents::ORDER_UPDATE_STATUS => array("update_status", 128)
        );
    }
}
