<?php
namespace Wbs24\Sbermmexport;

class ShipmentTest extends BitrixTestCase
{
    protected $defaultDeliveryDays = 3;
    protected $defaultOrderBefore = 12;
    protected $defaultStoreId = 1;

    public function testGetHeaderXml()
    {
        // входные параметры
        $param = [
            'deliveryDays' => '2',
            'orderBefore' => '14',
            'storeId' => '0',
        ];

        // результат для проверки
        $xmlTemplate = '<shipment-options><option days="%u" order-before="%u" store-id="%u"/></shipment-options>'."\n";
        $expectedResult = sprintf(
            $xmlTemplate,
            $param['deliveryDays'],
            $param['orderBefore'],
            $param['storeId']
        );

        // заглушка

        // вычисление результата
        $object = new Shipment($param);
        $result = $object->getHeaderXml();

        // проверка
        $this->assertEquals($expectedResult, $result);
    }

    public function testGetHeaderXmlWithEmptyGlobalSettings()
    {
        // входные параметры
        $param = [
            'deliveryDays' => '',
            'orderBefore' => '',
            'storeId' => '',
        ];

        // результат для проверки
        $xmlTemplate = '<shipment-options><option days="%u" order-before="%u" store-id="%u"/></shipment-options>'."\n";
        $expectedResult = sprintf(
            $xmlTemplate,
            $this->defaultDeliveryDays,
            $this->defaultOrderBefore,
            $this->defaultStoreId
        );

        // заглушка

        // вычисление результата
        $object = new Shipment($param);
        $result = $object->getHeaderXml();

        // проверка
        $this->assertEquals($expectedResult, $result);
    }

    public function testGetXml()
    {
        // входные параметры
        $param = [
            'deliveryDays' => '2',
            'orderBefore' => '14',
        ];

        $items = [];
        // item 1
        $items[0]['PROPERTIES'][2]['VALUE'] = '13';
        // item 2
        $items[1]['PROPERTIES'][1]['VALUE'] = '1';
        // item 3
        $items[2]['PROPERTIES'][1]['VALUE'] = '1';
        $items[2]['PROPERTIES'][2]['VALUE'] = '13';
        // item 4
        $items[3] = [];

        $fields = [
            'days' => 1,
            'order-before' => 2,
        ];

        // результат для проверки
        $expectedResultTemplate = '<shipment-options><option days="%u" order-before="%u"/></shipment-options>'."\n";
        $expectedResultValues = [ //
            [2, 13], // item 1 (первое значение days, второе order-before)
            [1, 14], // item 2
            [1, 13], // item 3
            [false, false], // item 4
        ];
        $expectedResultArray = [];
        foreach ($expectedResultValues as $values) {
            $expectedResultArray[] = (
                $values[0] !== false && $values[1] !== false
                ? sprintf($expectedResultTemplate, $values[0], $values[1])
                : ''
            );
        }

        // заглушка

        // обход условий
        $object = new Shipment($param);
        foreach ($items as $k => $item) {
            // вычисление результата
            $result = $object->getXml($item, $fields);

            // проверка
            $this->assertEquals($expectedResultArray[$k], $result);
        }
    }

    public function testGetXmlWithEmptyGlobalSettings()
    {
        // входные параметры
        $param = [
            'deliveryDays' => '',
            'orderBefore' => '',
        ];

        $items = [];
        // item 1
        $items[0]['PROPERTIES'][2]['VALUE'] = '13';
        // item 2
        $items[1]['PROPERTIES'][1]['VALUE'] = '1';
        // item 3
        $items[2]['PROPERTIES'][1]['VALUE'] = '2';
        $items[2]['PROPERTIES'][2]['VALUE'] = '14';
        // item 4
        $items[3] = [];

        $fields = [
            'days' => 1,
            'order-before' => 2,
        ];

        // результат для проверки
        $expectedResultTemplate = '<shipment-options><option days="%u" order-before="%u"/></shipment-options>'."\n";
        $expectedResultValues = [ //
            [$this->defaultDeliveryDays, 13], // item 1 (первое значение days, второе order-before)
            [1, $this->defaultOrderBefore], // item 2
            [2, 14], // item 3
            [false, false], // item 4
        ];
        $expectedResultArray = [];
        foreach ($expectedResultValues as $values) {
            $expectedResultArray[] = (
                $values[0] !== false && $values[1] !== false
                ? sprintf($expectedResultTemplate, $values[0], $values[1])
                : ''
            );
        }

        // заглушка

        // обход условий
        $object = new Shipment($param);
        foreach ($items as $k => $item) {
            // вычисление результата
            $result = $object->getXml($item, $fields);

            // проверка
            $this->assertEquals($expectedResultArray[$k], $result);
        }
    }
}
