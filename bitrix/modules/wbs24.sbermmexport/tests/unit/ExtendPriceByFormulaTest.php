<?php
namespace Wbs24\Sbermmexport;

use Bitrix\Main\Loader;

class ExtendPriceByFormulaTest extends BitrixTestCase
{
    public function test_getPrice()
    {
        // входные параметры
        $minPrice = 500;
        $fullPrice = 1000;
        $param = [
            'ignoreSale' => true,
            'formulaPrice' => '{PRICE} * 10',
        ];

        // результат для проверки
        $expectedResult = 10000;

        // заглушки

        // вычисление результата
        $object = new ExtendPriceByFormula($param);
        $result = $object->getPrice($minPrice, $fullPrice);

        // проверка
        $this->assertEquals($expectedResult, $result);
    }
}
