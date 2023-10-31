<?php
/**
 * Created by PhpStorm.
 * User: Dimitrii
 * Date: 31.03.2018
 * Time: 15:49
 */

namespace Dimitrii\PBCredit;

use Bitrix\Main,
    Bitrix\Main\Config\Option,
    Bitrix\Sale\Delivery,
    Bitrix\Sale\PaySystem,
    Bitrix\Sale\Basket,
    Bitrix\Sale\Order;

class PBCOrder
{

    protected $siteId;
    protected $data;
    protected $userId;
    protected $order;
    protected $currencyCode;
    protected $product;

    protected $error;

    protected $userType = [
        'phisic' => 1,
        'organization' => 2,
        'test' => 3,
    ];

    public function __construct($data)
    {
        if (!Main\Loader::includeModule('sale'))
            throw new Main\LoaderException(GetMessage("DI_SALE_MODULE_NOT_INSTALLED"));

        $this->siteId = $data['SITE_ID'] ? $data['SITE_ID']: 's1';
        $this->currencyCode = Option::get('sale', 'default_currency', 'RUB');

        $this->data = $data;
        $this->userId = $this->initUser();
        $this->order = $this->initOrder();
    }

    public function createOrder()
    {
        if (is_array($this->data) && $this->data['PRODUCT_ID'] && $this->data['IBLOCK_ID']) {
            $this->createBasket($this->data['IBLOCK_ID'], $this->data['PRODUCT_ID']);
            $this->initDelivery();
            $this->initPayment();

            $propertyCollection = $this->order->getPropertyCollection();
            $phoneProp = $propertyCollection->getPhone();
            $phoneProp->setValue($this->data['PHONE'] ? $this->data['PHONE'] : '');
            $nameProp = $propertyCollection->getPayerName();
            $nameProp->setValue($this->data['FIO'] ? $this->data['FIO'] : '');
            $this->order->save();

            $this->updateOrderStatus();
            $this->order->save();
        } else {
            return false;
        }
    }

    public function setId()
    {
            $this->updateOrderStatus();
            $this->order->save();
    }

    protected function updateOrderStatus()
    {

        if ($this->data['PAY_ID'] && $this->data['PAY_ID'] != '') {
            $arPaymentsCollection = $this->order->loadPaymentCollection();
            $currentPaymentOrder = $arPaymentsCollection->current();
            do {

                $currentPaymentOrder->setField('PS_STATUS_CODE', 'WORK');
                $currentPaymentOrder->setField('PS_STATUS_DESCRIPTION', GetMessage("DI_CUSTOMER_SENT_ORDER")  . $this->data['PAY_ID']);
                $currentPaymentOrder->setField('PS_STATUS_MESSAGE', $this->data['PAY_ID']);
                $currentPaymentOrder->save();

            } while ($currentPaymentOrder = $arPaymentsCollection->next());

            $this->order->setField('ADDITIONAL_INFO', GetMessage("DI_CUSTOMER_SENT_ORDER") . $this->data['PAY_ID']);
            $this->order->save();
        }

    }

    protected function initUser()
    {
        global $USER;
        if ($USER->getId()) {
            return $USER->getId();
        } else {
            return \CSaleUser::GetAnonymousUserID();
        }
    }

    protected function initProfile()
    {
        global $USER;
        if (!$USER->isAuthorized() || !$this->userId)
            $this->error = 'not isAuthorized';

        $dbUserProfiles = \CSaleOrderUserProps::GetList(
            array("ID" => "ASC"),
            array(
                //"PERSON_TYPE_ID" => $this->getPersonTypeId(),
                "USER_ID" => $this->userId)
        );
        if ($arUserProfiles = $dbUserProfiles->fetch())
            $this->profileId = $arUserProfiles;
    }

    private function getPersonTypeId()
    {
        return $this->userType['phisic'];
    }

    private function initOrder()
    {
        $orderIdFromRequest = false;
        $order = false;

        if ($this->data['ID'] && $this->data['ID'] != '') {
            $orderIdFromRequest = intval($this->data['ID']);
        }
        if ($orderIdFromRequest) {
            $order = Order::loadByAccountNumber($orderIdFromRequest);
            if (!$order)
                $order = Order::load($orderIdFromRequest);
        }

        if (!$order)
            $order = Order::create($this->siteId, $this->userId);
        return $order;
    }

    private function createBasket($moduleId, $productId)
    {
        $basket = Basket::create($this->siteId);

        $product = $basket->createItem($moduleId, $productId);
        $product->setFields(array(
            'NAME' => $this->data['NAME'],
            'PRICE' => doubleval($this->data['PRICE']),
            'CURRENCY' => $this->currencyCode,
            'QUANTITY' => intval($this->data['QUANTITY']) > 0 ? $this->data['QUANTITY'] : 1,
            //'LID' => $this->siteId,
            //'PRODUCT_PROVIDER_CLASS' => '\CCatalogProductProvider',
        ));

        $this->product = $product;
        $this->order->setPersonTypeId(intval($this->data['PERSONTYPE']) > 0? $this->data['PERSONTYPE']: 1);
        $this->order->setBasket($basket);
    }

    private function initDelivery()
    {
        $shipmentCollection = $this->order->getShipmentCollection();
        $service = Delivery\Services\Manager::getById(Delivery\Services\EmptyDeliveryService::getEmptyDeliveryServiceId());

        $shipment = $shipmentCollection->createItem(
           Delivery\Services\Manager::getObjectById($service['ID'])
        );

        $shipmentItemCollection = $shipment->getShipmentItemCollection();
        $shipmentItem = $shipmentItemCollection->createItem($this->product);
        $shipmentItem->setQuantity($this->product->getQuantity());
    }

    private function initPayment()
    {
        $paySystemId = intval($this->data['PAY_SYSTEM_ID']) > 0 ? $this->data['PAY_SYSTEM_ID'] : 1;

        $paymentCollection = $this->order->getPaymentCollection();
        $payment = $paymentCollection->createItem();
        $paySystemService = PaySystem\Manager::getObjectById($paySystemId);

        $payment->setFields(array(
            'PAY_SYSTEM_ID' => $paySystemService->getField("PAY_SYSTEM_ID"),
            'PAY_SYSTEM_NAME' => $paySystemService->getField("NAME"),
        ));

        $payment->setField("SUM", $this->order->getPrice());
        $payment->setField("CURRENCY", $this->order->getCurrency());
    }
}

