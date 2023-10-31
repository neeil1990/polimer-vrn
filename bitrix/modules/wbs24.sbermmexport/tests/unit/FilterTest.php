<?php
namespace Wbs24\Sbermmexport;

use Bitrix\Main\Loader;

class FilterTest extends BitrixTestCase
{
    public function testGetNeededPropertyIds()
    {
        // входные параметры
        $filter = "(((isset(\$element['PROPERTY_5_VALUE']) && in_array(58885, \$element['PROPERTY_5_VALUE'])) && (isset(\$element['PROPERTY_32_VALUE']) && CGlobalCondCtrl::LogicContain(\$element['PROPERTY_32_VALUE'], \"100\"))))";

        // результат для проверки
        $expectedResult = [5, 32];

        // заглушка

        // вычисление результата
        // вызов protected метода
        $method = $this->getMethod('Wbs24\\Sbermmexport\\Filter', 'getNeededPropertyIds');
        $object = new Filter();
        $result = $method->invokeArgs($object, [$filter]);

        // проверка
        $this->assertEquals($expectedResult, $result);
    }

    public function testIsAllPropertiesReady()
    {
        // входные параметры
        $element = [
            'PROPERTY_1_VALUE' => 'a',
            'PROPERTY_2_VALUE' => 'b',
        ];
        $propertyIdsArray = [
            [1, 2],
            [1, 3],
        ];

        // результат для проверки
        $expectedResultArray = [true, false];

        // заглушка

        // обход входных параметров
        foreach ($propertyIdsArray as $k => $propertyIds) {
            // вычисление результата
            // вызов protected метода
            $method = $this->getMethod('Wbs24\\Sbermmexport\\Filter', 'isAllPropertiesReady');
            $object = new Filter();
            $result = $method->invokeArgs($object, [$element, $propertyIds]);

            // проверка
            $this->assertEquals($expectedResultArray[$k], $result);
        }
    }

    public function testMergeProperties()
    {
        // входные параметры
        $element = [
            'ID' => 100,
            'PROPERTY_1_VALUE' => 'a',
            'PROPERTY_2_VALUE' => 'b',
        ];
        $parent = [
            'ID' => 1,
            'PROPERTY_11_VALUE' => 'c',
            'PROPERTY_12_VALUE' => 'd',
        ];

        // результат для проверки
        $expectedResult = [
            'ID' => 100,
            'PROPERTY_1_VALUE' => 'a',
            'PROPERTY_2_VALUE' => 'b',
            'PROPERTY_11_VALUE' => 'c',
            'PROPERTY_12_VALUE' => 'd',
        ];

        // заглушка

        // вычисление результата
        // вызов protected метода
        $method = $this->getMethod('Wbs24\\Sbermmexport\\Filter', 'mergeProperties');
        $object = new Filter();
        $result = $method->invokeArgs($object, [$element, $parent]);

        // проверка
        $this->assertEquals($expectedResult, $result);
    }

    public function testAddPropertiesToElement()
    {
		// входные параметры
        $element = [
            'ID' => 1,
            'IBLOCK_ID' => 1,
        ];

		// результат для проверки
		$expectedResult = [
            'ID' => 1,
            'IBLOCK_ID' => 1,
            'PROPERTY_100_VALUE' => [
                'a',
                'b',
            ],
            'PROPERTY_101_VALUE' => [
                'c',
            ],
        ];

		// заглушка
        Loader::includeModule('iblock');

        $CIBlockResultStub = $this->createMock(\CIBlockResult::class);
        $fetchResults = [
            [
                'IBLOCK_ELEMENT_ID' => 1,
                100 => [
                    'a',
                    'b',
                ],
                101 => 'c',
            ],
            false,
        ];
        $CIBlockResultStub->method('Fetch')
            ->will($this->onConsecutiveCalls(...$fetchResults));

        $CIBlockElementStub = $this->createMock(CIBlockElement::class);
        $CIBlockElementStub->method('GetPropertyValues')
            ->willReturn($CIBlockResultStub);

        // вычисление результата
        // вызов protected метода
        $method = $this->getMethod('Wbs24\\Sbermmexport\\Filter', 'addPropertiesToElement');
        $object = new Filter([
            'objects' => [
                'CIBlockElement' => $CIBlockElementStub,
            ],
        ]);
        $result = $method->invokeArgs($object, [$element]);

        // проверка
        $this->assertEquals($expectedResult, $result);
    }
}
