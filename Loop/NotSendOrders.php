<?php


namespace FreeShipping\Loop;

use FreeShipping\FreeShipping;
use Propel\Runtime\ActiveQuery\Criteria;
use Thelia\Core\Template\Loop\Argument\Argument;
use Thelia\Core\Template\Loop\Argument\ArgumentCollection;
use Thelia\Core\Template\Loop\Order;
use Thelia\Model\OrderQuery;
use Thelia\Model\OrderStatus;
use Thelia\Model\OrderStatusQuery;

class NotSendOrders extends Order
{
    public function getArgDefinitions()
    {
        return new ArgumentCollection(
            Argument::createBooleanTypeArgument('with_prev_next_info', false)
        );
    }

    public function buildModelCriteria()
    {
        $status = OrderStatusQuery::create()
            ->filterByCode(
                array(
                    OrderStatus::CODE_PAID,
                    OrderStatus::CODE_PROCESSING,
                ),
                Criteria::IN
            )
            ->find()
            ->toArray("code")
        ;
        $query = OrderQuery::create()
            ->filterByDeliveryModuleId(FreeShipping::getModuleId())
            ->filterByStatusId(
                array(
                    $status[OrderStatus::CODE_PAID]['Id'],
                    $status[OrderStatus::CODE_PROCESSING]['Id']),
                Criteria::IN
            );

        return $query;
    }
}
