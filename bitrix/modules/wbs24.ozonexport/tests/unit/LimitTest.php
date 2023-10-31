<?php
namespace Wbs24\Ozonexport;

class LimitTest extends BitrixTestCase
{
    public function testVerifyElementShowing()
    {
		// входные параметры
        $param = [
            'ignoreSale' => false,
            'extendPrice' => true,
            'limitPriceOn' => true,
            'limitMinPrice' => 100,
            'limitMaxPrice' => 200,
            'limitPriceBeforeExtPrice' => true,
        ];
        $elementPrices = [
            'minPrice' => 150,
            'fullPrice' => 210,
            'price' => 250,
            'oldPrice' => 200,
        ];

		// результат для проверки
		$expectedResult = true;

		// заглушка

        // вычисление результата
        $limit = new Limit($param);
        $result = $limit->verifyElementShowing([], $elementPrices);

        // проверка
        $this->assertEquals($expectedResult, $result);
    }

    public function testVerifyElementShowingOnlyMinPrice()
    {
		// входные параметры
        $param = [
            'ignoreSale' => false,
            'extendPrice' => true,
            'limitPriceOn' => true,
            'limitMinPrice' => 100,
            'limitMaxPrice' => '',
            'limitPriceBeforeExtPrice' => true,
        ];
        $elementPrices = [
            'minPrice' => 150,
            'fullPrice' => 210,
            'price' => 250,
            'oldPrice' => 200,
        ];

		// результат для проверки
		$expectedResult = true;

		// заглушка

        // вычисление результата
        $limit = new Limit($param);
        $result = $limit->verifyElementShowing([], $elementPrices);

        // проверка
        $this->assertEquals($expectedResult, $result);
    }

    public function testVerifyElementShowingWithIgnoreSale()
    {
		// входные параметры
        $param = [
            'ignoreSale' => true,
            'extendPrice' => true,
            'limitPriceOn' => true,
            'limitMinPrice' => 100,
            'limitMaxPrice' => 200,
            'limitPriceBeforeExtPrice' => true,
        ];
        $elementPrices = [
            'minPrice' => 150,
            'fullPrice' => 210,
            'price' => 250,
            'oldPrice' => 200,
        ];

		// результат для проверки
		$expectedResult = false;

		// заглушка

        // вычисление результата
        $limit = new Limit($param);
        $result = $limit->verifyElementShowing([], $elementPrices);

        // проверка
        $this->assertEquals($expectedResult, $result);
    }
}
