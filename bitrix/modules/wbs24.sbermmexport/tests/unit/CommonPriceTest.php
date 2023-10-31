<?php
namespace Wbs24\Sbermmexport;

class CommonPriceTest extends BitrixTestCase
{
    public function testSetParam()
    {
		// входные параметры
        $paramForInit = [
            'a' => 1,
            'b' => 0,
        ];
        $param = [
            'b' => 2,
            'c' => 3,
        ];

		// результат для проверки
        $expectedResult = [
            'a' => 1,
            'b' => 2,
            'c' => 3,
        ];;

		// заглушка

        // вычисление результата
        $priceObject = new CommonPrice($paramForInit);
        $priceObject->setParam($param);
        $result = $priceObject->getParam();

        // проверка
        $this->assertEquals($expectedResult, $result);
    }

    public function testGetPriceWithIgnoreSale()
    {
		// входные параметры
		$minPriceArray = [900];
		$fullPriceArray = [1000];

		// результат для проверки
		$expectedResultArray = [1000];

		// заглушка

		// обход условий
		$priceObject = new CommonPrice([
            'ignoreSale' => true,
        ]);
		foreach ($expectedResultArray as $k => $expectedResult) {
			// вычисление результата
			$result = $priceObject->getPrice($minPriceArray[$k], $fullPriceArray[$k]);

			// проверка
			$this->assertEquals($expectedResult, $result);
		}
    }

    public function testGetPrice()
    {
		// входные параметры
		$minPriceArray = [900];
		$fullPriceArray = [1000];

		// результат для проверки
		$expectedResultArray = [900];

		// заглушка

		// обход условий
		$priceObject = new CommonPrice;
		foreach ($expectedResultArray as $k => $expectedResult) {
			// вычисление результата
			$result = $priceObject->getPrice($minPriceArray[$k], $fullPriceArray[$k]);

			// проверка
			$this->assertEquals($expectedResult, $result);
		}
    }

    public function testGetOldPrice()
    {
		// входные параметры
		$minPriceArray = [900, 95, 99, 100, 105];
		$fullPriceArray = [1000, 100, 100, 100, 100];

		// результат для проверки
		$expectedResultArray = [1000, 100, 100, 0, 0];

		// заглушка

		// обход условий
		$priceObject = new CommonPrice;
		foreach ($expectedResultArray as $k => $expectedResult) {
			// вычисление результата
            $priceObject->getPrice($minPriceArray[$k], $fullPriceArray[$k]); // для создания условий для зупуска getOldPrice()
			$result = $priceObject->getOldPrice($minPriceArray[$k], $fullPriceArray[$k]);

			// проверка
			$this->assertEquals($expectedResult, $result);
		}
	}
}
