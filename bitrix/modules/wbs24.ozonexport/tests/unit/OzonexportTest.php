<?php
namespace Wbs24\Ozonexport;

class OzonexportTest extends BitrixTestCase
{
	public function testGetElementId()
	{
		// входные параметры
		$element = [
			'ID' => 25851,
			'EXTERNAL_ID' => 45265,
			'PROPERTIES' => [
				'9' => [
                    'VALUE' => '01800180',
					'~VALUE' => '01800180',
				],
			],
		];
		$secondParamVariantsArray = [
			'ID',
			'XML_ID',
			9,
		];

		// результат для проверки
		$expectedResultArray = [
			25851,
			45265,
			'01800180',
		];

		// заглушка

		// обход условий
		$ozon = new \Wbs24\Ozonexport;
		foreach ($expectedResultArray as $k => $expectedResult) {
			// вычисление результата
			$result = $ozon->getElementId($element, $secondParamVariantsArray[$k]);

			// проверка
			$this->assertEquals($expectedResult, $result);
		}
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
		$ozon = new \Wbs24\Ozonexport;
		$result = $ozon->cleanKeysFromQuotes($array);

		// проверка
		$this->assertEquals($expectedResult, $result);
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
        $method = $this->getMethod('Wbs24\\Ozonexport', 'getFilesByExample');
        $object = new \Wbs24\Ozonexport();
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
            $method = $this->getMethod('Wbs24\\Ozonexport', 'getExportTime');
            $object = new \Wbs24\Ozonexport();
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
        $method = $this->getMethod('Wbs24\\Ozonexport', 'getFilesBeforeTime');
        $object = new \Wbs24\Ozonexport();
        $result = $method->invokeArgs($object, [
            $filesList,
            $beforeTimestamp,
        ]);

        // проверка
        $this->assertEquals($expectedResult, $result);
    }
}
