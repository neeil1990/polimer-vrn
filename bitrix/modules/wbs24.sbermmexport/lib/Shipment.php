<?php
namespace Wbs24\Sbermmexport;

class Shipment
{
    protected $param;

    protected $defaultDeliveryDays = 3;
    protected $defaultOrderBefore = 12;
    protected $defaultStoreId = 1;

    function __construct($param = [])
    {
        $this->setParam($param);
    }

    public function setParam($param)
    {
        foreach ($param as $name => $value) {
            $this->param[$name] = $value;
        }
    }

    public function getParam()
    {
        return $this->param;
    }

    public function getHeaderXml()
    {
        $days = $this->getGlobalDeliveryDays();
        $orderBefore = $this->getGlobalOrderBefore();
        $storeId = $this->param['storeId'] ?? '';
        $storeId = ($storeId === '' ? $this->defaultStoreId : $storeId);

        $xmlTemplate = '<shipment-options><option days="%u" order-before="%u" store-id="%u"/></shipment-options>'."\n";
        $xml = sprintf($xmlTemplate, $days, $orderBefore, $storeId);

        return $xml;
    }

    public function getXml($item, $fields)
    {
        $xml = '';

        $globalDays = $this->getGlobalDeliveryDays();
        $globalOrderBefore = $this->getGlobalOrderBefore();

        $itemDays = $this->getPropertyValue($item, $fields['days']);
        $itemDays = $this->normalizeDeliveryDays($itemDays);
        $itemOrderBefore = $this->getPropertyValue($item, $fields['order-before']);
        $itemOrderBefore = $this->normalizeOrderBefore($itemOrderBefore);
        $itemSettingsIsEmpty = ($itemDays === false && $itemOrderBefore === false ? true : false);

        if (!$itemSettingsIsEmpty) {
            $days = $itemDays !== false ? $itemDays : $globalDays;
            $orderBefore = $itemOrderBefore ?: $globalOrderBefore;

            $xmlTemplate = '<shipment-options><option days="%u" order-before="%u"/></shipment-options>'."\n";
            $xml = sprintf($xmlTemplate, $days, $orderBefore);
        }

        return $xml;
    }

    protected function getGlobalDeliveryDays()
    {
        $value = $this->param['deliveryDays'] ?? $this->defaultDeliveryDays;

        return $this->normalizeDeliveryDays($value, $this->defaultDeliveryDays);
    }

    protected function getGlobalOrderBefore()
    {
        $value = $this->param['orderBefore'] ?? $this->defaultOrderBefore;

        return $this->normalizeOrderBefore($value, $this->defaultOrderBefore);
    }

    protected function normalizeDeliveryDays($value, $defaultValue = false)
    {
        if (!is_numeric($value) || $value < 0) {
            $value = $defaultValue;
        }

        return $value;
    }

    protected function normalizeOrderBefore($value, $defaultValue = false)
    {
        if (!is_numeric($value) || $value < 1 || $value > 23) {
            $value = $defaultValue;
        }

        return $value;
    }

    protected function getPropertyValue($item, $propertyId)
    {
        $value = $item['PROPERTIES'][$propertyId]['VALUE'] ?? false;

        return $value;
    }
}
