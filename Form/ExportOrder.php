<?php


namespace FreeShipping\Form;

use FreeShipping\FreeShipping;
use Propel\Runtime\ActiveQuery\Criteria;
use Thelia\Core\Translation\Translator;
use Thelia\Form\BaseForm;
use Symfony\Component\Validator\Constraints;
use Thelia\Model\OrderQuery;
use Thelia\Model\OrderStatus;
use Thelia\Model\OrderStatusQuery;

class ExportOrder extends BaseForm
{

    protected function buildForm()
    {
        $status = OrderStatusQuery::create()
            ->filterByCode(
                array(
                    OrderStatus::CODE_PAID,
                    OrderStatus::CODE_PROCESSING,
                    OrderStatus::CODE_SENT
                ),
                Criteria::IN
            )
            ->find()
            ->toArray("code")
        ;
        $query = OrderQuery::create()
            ->filterByDeliveryModuleId(FreeShipping::getModuleId())
            ->filterByStatusId(array($status['paid']['Id'], $status['processing']['Id']), Criteria::IN)
            ->find();

        $this->formBuilder
            ->add('new_status_id', 'choice',array(
                    'label' => Translator::getInstance()->trans('server'),
                    'choices' => array(
                        "nochange" => Translator::getInstance()->trans("Do not change"),
                        "processing" => Translator::getInstance()->trans("Set orders status as processing"),
                        "sent" => Translator::getInstance()->trans("Set orders status as sent")
                    ),
                    'required' => 'true',
                    'expanded'=>true,
                    'multiple'=>false,
                    'data'=>'nochange'
                )
            );

        /** @var \Thelia\Model\Order $order */
        foreach ($query as $order) {
            $this->formBuilder->add("order_".$order->getId(), "checkbox", array(
                'label'=>$order->getRef(),
                'label_attr'=>array('for'=>'export_'.$order->getId())
            ));
        }
    }
    
    public function getName()
    {
        return "exportfreeshippingorder";
    }

}
