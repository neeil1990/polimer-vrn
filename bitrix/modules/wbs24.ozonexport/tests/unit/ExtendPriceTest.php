<?php
namespace Wbs24\Ozonexport;

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
		$minPrice = 800;
		$fullPrice = 900; // заглушка - в расчетах не учавствует
		$oldPricePlusPercentArray = [3, 20];

		// результат для проверки
		$expectedResultArray = [0, 1000];

		// заглушка

		// обход условий
		$priceObject = new ExtendPrice;
		foreach ($expectedResultArray as $k => $expectedResult) {
			// вычисление результата
			$priceObject->setParam([
				'oldPricePlusPercent' => $oldPricePlusPercentArray[$k],
			]);
			$result = $priceObject->getOldPrice($minPrice, $fullPrice);

			// проверка
			$this->assertEquals($expectedResult, $result);
		}
    }

    public function testGetOldPriceForMore10k()
    {
		// входные параметры
		$minPriceArray = [16000, 19400, 16000];
		$fullPrice = 20000; // заглушка - в расчетах не учавствует
		$oldPricePlusPercentArray = [2, 3, 20];

		// результат для проверки
		$expectedResultArray = [0, 20000, 20000];

		// заглушка

		// обход условий
		$priceObject = new ExtendPrice;
		foreach ($expectedResultArray as $k => $expectedResult) {
			// вычисление результата
			$priceObject->setParam([
				'oldPrice10kPlusPercent' => $oldPricePlusPercentArray[$k],
			]);
			$result = $priceObject->getOldPrice($minPriceArray[$k], $fullPrice);

			// проверка
			$this->assertEquals($expectedResult, $result);
		}
    }

    public function testGetPremiumPrice()
    {
		// входные параметры
		$minPrice = 1000;
		$fullPrice = 1500;
		$premiumPriceMinusPercentArray = [-5, 0, 10, 20, 30];

		// результат для проверки
		$expectedResultArray = [0, 0, 900, 800, 700];

		// заглушка

		// обход условий
		$priceObject = new ExtendPrice;
		foreach ($expectedResultArray as $k => $expectedResult) {
			// вычисление результата
			$priceObject->setParam([
				'premiumPriceMinusPercent' => $premiumPriceMinusPercentArray[$k],
			]);
			$result = $priceObject->getPremiumPrice($minPrice, $fullPrice);

			// проверка
			$this->assertEquals($expectedResult, $result);
		}
    }
}