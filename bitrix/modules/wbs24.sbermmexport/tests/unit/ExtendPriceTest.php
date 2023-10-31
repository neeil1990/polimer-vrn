<?php
namespace Wbs24\Sbermmexport;

class ExtendPriceTest extends BitrixTestCase
{
    public function testGetPriceWithIgnoreSale()
    {
		// входные параметры
		$minPrice = 500;
		$fullPrice = 1000;
		$plusPercentArray = [-10, -5, 0, 5, 10];
        $plusAdditionalSumArray = [0, 49, 100, 100, 100];

		// результат для проверки
		$expectedResultArray = [900, 999, 1100, 1150, 1200];

		// заглушка

		// обход условий
		$priceObject = new ExtendPrice([
            'ignoreSale' => true,
        ]);
		foreach ($expectedResultArray as $k => $expectedResult) {
			// вычисление результата
			$priceObject->setParam([
				'plusPercent' => $plusPercentArray[$k],
				'plusAdditionalSum' => $plusAdditionalSumArray[$k],
			]);
			$result = $priceObject->getPrice($minPrice, $fullPrice);

			// проверка
			$this->assertEquals($expectedResult, $result);
		}
    }

    public function testGetPrice()
    {
		// входные параметры
		$minPrice = 1000;
		$fullPrice = 1500;
		$plusPercentArray = [-10, -5, 0, 5, 10];
        $plusAdditionalSumArray = [0, 49, 100, 100, 100];

		// результат для проверки
		$expectedResultArray = [900, 999, 1100, 1150, 1200];

		// заглушка

		// обход условий
		$priceObject = new ExtendPrice;
		foreach ($expectedResultArray as $k => $expectedResult) {
			// вычисление результата
			$priceObject->setParam([
				'plusPercent' => $plusPercentArray[$k],
				'plusAdditionalSum' => $plusAdditionalSumArray[$k],
			]);
			$result = $priceObject->getPrice($minPrice, $fullPrice);

			// проверка
			$this->assertEquals($expectedResult, $result);
		}
    }

    public function testGetOldPrice()
    {
		// входные параметры
		$minPriceArray = [970, 800, 800];
		$fullPrice = 1000; // в рассчете не используется
		$oldPricePlusPercentArray = [3, 20, 0];

		// результат для проверки
		$expectedResultArray = [1000, 1000, 0];

		// заглушка

		// обход условий
		$priceObject = new ExtendPrice;
		foreach ($expectedResultArray as $k => $expectedResult) {
			// вычисление результата
			$priceObject->setParam([
				'oldPricePlusPercent' => $oldPricePlusPercentArray[$k],
			]);
			$result = $priceObject->getOldPrice($minPriceArray[$k], $fullPrice);

			// проверка
			$this->assertEquals($expectedResult, $result);
		}
    }
}
