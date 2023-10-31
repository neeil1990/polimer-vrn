<?php
namespace Wbs24\Sbermmexport;

class SbermmexportTest extends BitrixTestCase
{
    public function _testGetPreparedPicture()
    {
        // входные параметры
        $row = [
            'DETAIL_PICTURE' => 1,
            'PREVIEW_PICTURE' => 2,
        ];
        $options = [
            'PROTOCOL' => 'https://',
            'SITE_NAME' => 'test.com',
            'PICTURE' => 'PREVIEW_PICTURE',
        ];
        $pictureFileArray = [
            'SRC' => '/upload/1.jpg',
        ];

        // результат для проверки
        $expectedResult = $options['PROTOCOL'].$options['SITE_NAME'].$pictureFileArray['SRC'];

        // заглушка
        $CFileStub = $this->createMock(CFile::class);
        $CFileStub->method('GetFileArray')
            ->with($this->equalTo(2))
            ->willReturn($pictureFileArray);

        // вычисление результата
        $sber = new \Wbs24\Sbermmexport([
            'objects' => [
                'CFile' => $CFileStub,
            ],
        ]);
        $result = $sber->getPreparedPicture($row, $options);

        // проверка
        $this->assertEquals($expectedResult, $result);
    }

    public function _testGetPreparedPictureWithOptionAuto()
    {
        // входные параметры
        $row = [
            'DETAIL_PICTURE' => 0,
            'PREVIEW_PICTURE' => 2,
        ];
        $options = [
            'PROTOCOL' => 'https://',
            'SITE_NAME' => 'test.com',
            'PICTURE' => 'AUTO',
        ];
        $pictureFileArray = [
            'SRC' => '/upload/1.jpg',
        ];

        // результат для проверки
        $expectedResult = $options['PROTOCOL'].$options['SITE_NAME'].$pictureFileArray['SRC'];

        // заглушка
        $CFileStub = $this->createMock(CFile::class);
        $CFileStub->method('GetFileArray')
            ->with($this->equalTo(2))
            ->willReturn($pictureFileArray);

        // вычисление результата
        $sber = new \Wbs24\Sbermmexport([
            'objects' => [
                'CFile' => $CFileStub,
            ],
        ]);
        $result = $sber->getPreparedPicture($row, $options);

        // проверка
        $this->assertEquals($expectedResult, $result);
    }

    public function testCleanKeysFromQuotes()
    {
        // входные параметры
        $array = [
            '"extendPrice"' => 'Y',
            '"plusPercent"' => 10,
            '"plusAdditionalSum"' => 99,
            '"oldPriceMinusPercent"' => 5,
        ];

        // результат для проверки
        $expectedResult = [
            'extendPrice' => 'Y',
            'plusPercent' => 10,
            'plusAdditionalSum' => 99,
            'oldPriceMinusPercent' => 5,
        ];

        // заглушка

        // вычисление результата
        $sber = new \Wbs24\Sbermmexport;
        $result = $sber->cleanKeysFromQuotes($array);

        // проверка
        $this->assertEquals($expectedResult, $result);
    }

    public function testAppendFile()
    {
        // входные параметры
        $testFileName = $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/wbs24.sbermmexport/tests/unit/files/test.txt';
        $resultFileName = $_SERVER['DOCUMENT_ROOT'].'/upload/test'.time().'.txt';

        // результат для проверки
        $expectedResult = true;
        $expectedResultFile = file_get_contents($testFileName);

        // заглушка

        // вычисление результата
        $sber = new \Wbs24\Sbermmexport;
        $result = $sber->appendFile($testFileName, $resultFileName);
        $resultFile = file_get_contents($resultFileName);
        unlink($resultFileName);

        // проверка
        $this->assertEquals($expectedResult, $result);
        $this->assertEquals($expectedResultFile, $resultFile);
    }

    public function testGetFilesByExample()
    {
        // входные параметры
        $filesList = [
            '.',
            '..',
            '/full/path/ozon_67688.php_import_20220419_2005.php',
            '/full/path/ozon_67688.php_import_20220420_1707.php',
            '/full/path/ozon_67688.php_import_20220420_1815.php',
            '/full/path/ozon_67688.php_import_20220420_1830.php',
            '/full/path/testexport.php_import_20220420_1535.php',
            '/full/path/testexport.php_import_20220420_1930.php',
        ];
        $example = '/full/path/ozon_67688.php_import_20220420_1815.php';

        // результат для проверки
        $expectedResult = [
            '/full/path/ozon_67688.php_import_20220419_2005.php',
            '/full/path/ozon_67688.php_import_20220420_1707.php',
            '/full/path/ozon_67688.php_import_20220420_1815.php',
            '/full/path/ozon_67688.php_import_20220420_1830.php',
        ];

        // заглушка

        // вычисление результата
        $method = $this->getMethod('Wbs24\\Sbermmexport', 'getFilesByExample');
        $object = new \Wbs24\Sbermmexport();
        $result = $method->invokeArgs($object, [
            $filesList,
            $example,
        ]);

        // проверка
        $this->assertEquals($expectedResult, $result);
    }

    public function testGetExportTime()
    {
        // входные параметры
        $files = [
            '/full/path/ozon_67688.php_import_20220420_1815.php',
            '/full/path/incorrect_file_name.php'
        ];

        // результат для проверки
        $expectedResults = [
            202204201815,
            null,
        ];

        // заглушка

        foreach ($files as $k => $file) {
            // вычисление результата
            $method = $this->getMethod('Wbs24\\Sbermmexport', 'getExportTime');
            $object = new \Wbs24\Sbermmexport();
            $result = $method->invokeArgs($object, [
                $file,
            ]);

            // проверка
            $this->assertEquals($expectedResults[$k], $result);
        }
    }

    public function testGetFilesBeforeTime()
    {
        // входные параметры
        $filesList = [
            '/full/path/bad_name.php',
            '/full/path/ozon_67688.php_import_20220419_2005.php',
            '/full/path/ozon_67688.php_import_20220420_1707.php',
            '/full/path/ozon_67688.php_import_20220420_1815.php',
            '/full/path/ozon_67688.php_import_20220420_1830.php',
        ];
        $beforeTimestamp = 202204201815;

        // результат для проверки
        $expectedResult = [
            '/full/path/ozon_67688.php_import_20220419_2005.php',
            '/full/path/ozon_67688.php_import_20220420_1707.php',
        ];

        // заглушка

        // вычисление результата
        $method = $this->getMethod('Wbs24\\Sbermmexport', 'getFilesBeforeTime');
        $object = new \Wbs24\Sbermmexport();
        $result = $method->invokeArgs($object, [
            $filesList,
            $beforeTimestamp,
        ]);

        // проверка
        $this->assertEquals($expectedResult, $result);
    }

    public function testGetAdditionalPhotos()
    {
        // входные параметры
        $value = 'test.jpg, test2.jpg';

        // результат для проверки
        $expectedResult = '<picture>test.jpg</picture><picture>test2.jpg</picture>';

        // заглушка

        // вычисление результата
        $method = $this->getMethod('Wbs24\\Sbermmexport', 'getAdditionalPhotos');
        $object = new \Wbs24\Sbermmexport();
        $result = $method->invokeArgs($object, [$value]);

        // проверка
        $this->assertEquals($expectedResult, $result);
    }

    public function testIsManualCall()
    {
        // входные параметры
        $traceArray = [
            '[{"file":"/home/bitrix/www/bitrix/php_interface/include/catalog_export/sbermm_run.php","line":3,"function":"require"},{"file":"/home/bitrix/www/bitrix/modules/catalog/admin/export_setup.php","line":310,"args":["/home/bitrix/www/bitrix/php_interface/include/catalog_export/sbermm_run.php"],"function":"include"},{"file":"/home/bitrix/www/bitrix/admin/cat_export_setup.php","line":2,"args":["/home/bitrix/www/bitrix/modules/catalog/admin/export_setup.php"],"function":"require_once"}]',
            '[{"file":"C:\\\\sites\\\\santehstroy\\\\bitrix\\\\php_interface\\\\include\\\\catalog_export\\\\sbermm_run.php","line":3,"function":"require"},{"file":"C:\\\\sites\\\\santehstroy\\\\bitrix\\\\modules\\\\catalog\\\\admin\\\\export_setup.php","line":310,"args":["C:\\\\sites\\\\santehstroy\\\\bitrix\\\\php_interface\\\\include\\\\catalog_export\\\\sbermm_run.php"],"function":"include"},{"file":"C:\\\\sites\\\\santehstroy\\\\bitrix\\\\admin\\\\cat_export_setup.php","line":2,"args":["C:\\\\sites\\\\santehstroy\\\\bitrix\\\\modules\\\\catalog\\\\admin\\\\export_setup.php"],"function":"require_once"}]',
            '[{"file":"C:\\\\sites\\\\santehstroy\\\\bitrix\\\\php_interface\\\\include\\\\catalog_export\\\\sbermm_run.php","line":3,"function":"require"},{"file":"C:\\\\sites\\\\santehstroy\\\\bitrix\\\\modules\\\\catalog\\\\general\\\\catalog_export.php","line":315,"args":["C:\\\\sites\\\\santehstroy\\\\bitrix\\\\php_interface\\\\include\\\\catalog_export\\\\sbermm_run.php"],"function":"include"},{"file":"C:\\\\sites\\\\santehstroy\\\\bitrix\\\\modules\\\\main\\\\classes\\\\mysql\\\\agent.php(166) : eval()`d code","line":1,"function":"PreGenerateExport","class":"CAllCatalogExport","type":"::","args":[14]},{"file":"C:\\\\sites\\\\santehstroy\\\\bitrix\\\\modules\\\\main\\\\classes\\\\mysql\\\\agent.php","line":166,"function":"eval"},{"file":"C:\\\\sites\\\\santehstroy\\\\bitrix\\\\modules\\\\askaron.agents\\\\include.php","line":59,"function":"ExecuteAgents","class":"CAgent","type":"::","args":[""]},{"file":"C:\\\\sites\\\\santehstroy\\\\bitrix\\\\modules\\\\askaron.agents\\\\include.php","line":18,"function":"CheckAgents","class":"CAskaronAgents","type":"::","args":[]},{"file":"C:\\\\sites\\\\santehstroy\\\\bitrix\\\\modules\\\\main\\\\classes\\\\general\\\\module.php","line":480,"function":"OnPageStartHandler","class":"CAskaronAgents","type":"::","args":[]},{"file":"C:\\\\sites\\\\santehstroy\\\\bitrix\\\\modules\\\\main\\\\include.php","line":173,"function":"ExecuteModuleEventEx","args":[{"SORT":"500","TO_MODULE_ID":"askaron.agents","TO_PATH":"","TO_CLASS":"CAskaronAgents","TO_METHOD":"OnPageStartHandler","TO_METHOD_ARG":[],"VERSION":"1","TO_NAME":"CAskaronAgents::OnPageStartHandler (askaron.agents)","FROM_DB":true,"FROM_MODULE_ID":"main","MESSAGE_ID":"OnPageStart"}]},{"file":"C:\\\\sites\\\\santehstroy\\\\bitrix\\\\modules\\\\main\\\\include\\\\prolog_before.php","line":14,"args":["C:\\\\sites\\\\santehstroy\\\\bitrix\\\\modules\\\\main\\\\include.php"],"function":"require_once"},{"file":"C:\\\\sites\\\\santehstroy\\\\bitrix\\\\modules\\\\main\\\\tools\\\\cron_events.php","line":11,"args":["C:\\\\sites\\\\santehstroy\\\\bitrix\\\\modules\\\\main\\\\include\\\\prolog_before.php"],"function":"require"}]',
            '[{"file":"C:\\\\sites\\\\santehstroy\\\\bitrix\\\\php_interface\\\\include\\\\catalog_export\\\\sbermm_run.php","line":3,"function":"require"},{"file":"C:\\\\sites\\\\santehstroy\\\\bitrix\\\\php_interface\\\\include\\\\catalog_export\\\\cron_frame.php","line":93,"args":["C:\\\\sites\\\\santehstroy\\\\bitrix\\\\php_interface\\\\include\\\\catalog_export\\\\sbermm_run.php"],"function":"include"}]',
        ];

        // результат для проверки
        $expectedResultArray = [
            true,
            true,
            false,
            false,
        ];

        // заглушка

        // вычисление результата
        $method = $this->getMethod('Wbs24\\Sbermmexport', 'isManualCall');
        foreach ($traceArray as $key => $jsonTrace) {
            $object = new \Wbs24\Sbermmexport();
            $trace = json_decode($jsonTrace, true);
            $result = $method->invokeArgs($object, [$trace]);

            // проверка
            $this->assertEquals($expectedResultArray[$key], $result);
        }
    }

    public function testGetName()
    {
        // входные параметры
        $element = [
            'NAME' => 'Product',
            'TYPE' => 1,
            'PROPERTIES' => [
                1 => [
                    'VALUE' => 'prop value'
                ],
                2 => [
                    'VALUE' => 'other'
                ],
            ],
        ];
        $settings = [
            'PRODUCT_NAME' => '{NAME} and {PROPERTY_2}, {PROPERTY_1}.',
        ];

        // результат для проверки
        $expectedResult = 'Product and other, prop value.';

        // заглушка

        // вычисление результата
        $object = new \Wbs24\Sbermmexport();
        $result = $object->getName($element, $settings);

        // проверка
        $this->assertEquals($expectedResult, $result);
    }

    public function testGetNameWithParentProperties()
    {
        // входные параметры
        $element = [
            'NAME' => 'Offer',
            'TYPE' => 4,
            'PROPERTIES' => [
                3 => [
                    'VALUE' => 'offer prop'
                ],
            ],
        ];
        $settings = [
            'OFFER_NAME' => '{NAME} and {PROPERTY_2}, {PROPERTY_1} - {PROPERTY_3}.',
        ];
        $parentProperties = [
            1 => [
                'VALUE' => 'prop value'
            ],
            2 => [
                'VALUE' => 'other'
            ],
        ];

        // результат для проверки
        $expectedResult = 'Offer and other, prop value - offer prop.';

        // заглушка

        // вычисление результата
        $object = new \Wbs24\Sbermmexport();
        $result = $object->getName($element, $settings, $parentProperties);

        // проверка
        $this->assertEquals($expectedResult, $result);
    }

    public function testGetNameWithEmptyTemplate()
    {
        // входные параметры
        $element = [
            'NAME' => 'Product',
            'TYPE' => 1,
        ];
        $settings = [];

        // результат для проверки
        $expectedResult = 'Product';

        // заглушка

        // вычисление результата
        $object = new \Wbs24\Sbermmexport();
        $result = $object->getName($element, $settings);

        // проверка
        $this->assertEquals($expectedResult, $result);
    }

    public function testGetNameForOffer()
    {
        // входные параметры
        $element = [
            'NAME' => 'Offer',
            'TYPE' => 4,
            'PROPERTIES' => [
                1 => [
                    'VALUE' => 'prop value'
                ],
                2 => [
                    'VALUE' => 'other'
                ],
            ],
        ];
        $settings = [
            'OFFER_NAME' => '{NAME} and {PROPERTY_2}, {PROPERTY_1}{PROPERTY_3}.',
        ];

        // результат для проверки
        $expectedResult = 'Offer and other, prop value.';

        // заглушка

        // вычисление результата
        $object = new \Wbs24\Sbermmexport();
        $result = $object->getName($element, $settings);

        // проверка
        $this->assertEquals($expectedResult, $result);
    }
}
