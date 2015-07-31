<?php


namespace FreeShipping\Controller\Admin;

use FreeShipping\Form\ExportOrder;
use FreeShipping\Format\CSV;
use FreeShipping\Format\CSVLine;
use FreeShipping\FreeShipping;
use Propel\Runtime\ActiveQuery\Criteria;
use Symfony\Component\Config\Definition\Exception\Exception;
use Thelia\Controller\Admin\BaseAdminController;
use Thelia\Core\Event\Order\OrderEvent;
use Thelia\Core\Event\TheliaEvents;
use Thelia\Core\HttpFoundation\Response;
use Thelia\Core\Security\AccessManager;
use Thelia\Core\Security\Resource\AdminResources;
use Thelia\Model\ConfigQuery;
use Thelia\Model\CountryQuery;
use Thelia\Model\CustomerTitleI18nQuery;
use Thelia\Model\OrderAddressQuery;
use Thelia\Model\OrderQuery;
use Thelia\Model\OrderStatus;
use Thelia\Model\OrderStatusQuery;

class Export extends BaseAdminController
{
    const CSV_SEPARATOR = ";";

    const DEFAULT_PHONE = "0100000000";
    const DEFAULT_CELLPHONE = "0600000000";

    public function export()
    {
        if (null !== $response = $this->checkAuth(array(AdminResources::MODULE), array('FreeShipping'), AccessManager::UPDATE)) {
            return $response;
        }

        $csv = new CSV(self::CSV_SEPARATOR);

        try {
            $form  = new ExportOrder($this->getRequest());
            $vform = $this->validateForm($form);

            // Check status_id
            $status_id = $vform->get("new_status_id")->getData();
            if (!preg_match("#^nochange|processing|sent$#", $status_id)) {
                throw new Exception("Bad value for new_status_id field");
            }

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
                ->filterByStatusId(
                    array(
                        $status[OrderStatus::CODE_PAID]['Id'],
                        $status[OrderStatus::CODE_PROCESSING]['Id']),
                    Criteria::IN
                )
                ->find();

            // check form && exec csv
            /** @var \Thelia\Model\Order $order */
            foreach ($query as $order) {
                $value = $vform->get('order_'.$order->getId())->getData();

                // If checkbox is checked
                if ($value) {
                    /**
                     * Retrieve user with the order
                     */
                    $customer = $order->getCustomer();

                    /**
                     * Retrieve address with the order
                     */
                    $address = OrderAddressQuery::create()
                        ->findPk($order->getDeliveryOrderAddressId());

                    if ($address === null) {
                        throw new Exception("Could not find the order's invoice address");
                    }

                    /**
                     * Retrieve country with the address
                     */
                    $country = CountryQuery::create()
                        ->findPk($address->getCountryId());

                    if ($country === null) {
                        throw new Exception("Could not find the order's country");
                    }

                    /**
                     * Retrieve Title
                     */
                    $title = CustomerTitleI18nQuery::create()
                        ->filterById($customer->getTitleId())
                        ->findOneByLocale(
                            $this->getSession()
                                ->getAdminEditionLang()
                                ->getLocale()
                        );

                    /**
                     * Get user's phone & cellphone
                     * First get invoice address phone,
                     * If empty, try to get default address' phone.
                     * If still empty, set default value
                     */
                    $phone = $address->getPhone();
                    if (empty($phone)) {
                        $phone = $customer->getDefaultAddress()->getPhone();

                        if (empty($phone)) {
                            $phone = self::DEFAULT_PHONE;
                        }
                    }

                    /**
                     * Cellp
                     */
                    $cellphone = $customer->getDefaultAddress()->getCellphone();

                    if (empty($cellphone)) {
                        $cellphone = self::DEFAULT_CELLPHONE;
                    }
                    /**
                     * Compute package weight
                     */
                    $weight = 0;
                    /** @var \Thelia\Model\OrderProduct $product */
                    foreach ($order->getOrderProducts() as $product) {
                        $weight+=(double) $product->getWeight();
                    }

                    /**
                     * Get store's name
                     */
                    $store_name = ConfigQuery::read("store_name");
                    /**
                     * Write CSV line
                     */
                    $csv->addLine(
                        CSVLine::create(
                            array(
                                $address->getFirstname(),
                                $address->getLastname(),
                                $address->getCompany(),
                                $address->getAddress1(),
                                $address->getAddress2(),
                                $address->getAddress3(),
                                $address->getZipcode(),
                                $address->getCity(),
                                $country->getIsoalpha2(),
                                $phone,
                                $cellphone,
                                $order->getRef(),
                                $title->getShort(),
                                $customer->getEmail(),
                                $weight,
                                $store_name,
                            )
                        )
                    );

                    /**
                     * Then update order's status if necessary
                     */
                    if ($status_id == "processing") {
                        $event = new OrderEvent($order);
                        $event->setStatus($status[OrderStatus::CODE_PROCESSING]['Id']);
                        $this->dispatch(TheliaEvents::ORDER_UPDATE_STATUS, $event);
                    } elseif ($status_id == "sent") {
                        $event = new OrderEvent($order);
                        $event->setStatus($status[OrderStatus::CODE_SENT]['Id']);
                        $this->dispatch(TheliaEvents::ORDER_UPDATE_STATUS, $event);
                    }

                }
            }

        } catch (\Exception $e) {
            return Response::create($e->getMessage(), 500);
        }

        return Response::create(
            utf8_decode($csv->parse()),
            200,
            array(
                "Content-Encoding"=>"ISO-8889-1",
                "Content-Type"=>"application/csv-tab-delimited-table",
                "Content-disposition"=>"filename=export.csv"
            )
        );
    }
}
