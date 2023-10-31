<?php
namespace Wbs24\Sbermmexport;

use Bitrix\Main\Loader;

class CalcExpressionTest extends BitrixTestCase
{
    public function test_run()
    {
        // входные параметры
        $expressions = [
            '(100 + (2 * 1000) + (1000 * 3)) / 2 - 200',
            '(100 + (1.1 * 1000) + (1000 * 1.2)) / 2 - 200',
        ];

        // результат для проверки
        $expectedResults = [
            2350,
            1000,
        ];

        // заглушки

        // вычисление результата
        foreach ($expressions as $key => $expression) {
            $object = new CalcExpression();
            $result = $object->run($expression);

            // проверка
            $this->assertEquals($expectedResults[$key], $result);
        }
    }
}
