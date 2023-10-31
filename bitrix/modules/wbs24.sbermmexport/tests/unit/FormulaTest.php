<?php
namespace Wbs24\Sbermmexport;

use Bitrix\Main\Loader;

class FormulaTest extends BitrixTestCase
{
    public function test_calc()
    {
        // входные параметры
        $fields = [
            'PRICE' => 1000,
        ];
        $formula = '(100 + (1.1 * {PRICE}) + (1.2 * {PRICE})) / 2';

        // результат для проверки
        $expectedResult = '(100 + (1.1 * 1000) + (1.2 * 1000)) / 2';

        // заглушки
        $CalcExpressionStub = $this->getMockBuilder(\stdClass::class)
            ->setMethods(['run'])
            ->getMock();
        // проверка
        $CalcExpressionStub->expects($this->once())
            ->method('run')
            ->with($this->equalTo($expectedResult));

        // вычисление результата
        $object = new Formula([
            'CalcExpression' => $CalcExpressionStub,
        ]);
        $object->setFormula($formula);
        $result = $object->calc($fields);
    }

    public function test_cleanFormula()
    {
        // входные параметры
        $formula = '(100 + <&mnsp;(1,1 * {PURCHASE_PRICE})>!&%garbage + ({PRICE} * 1.2)) / 2 - 200';
        $marks = [
            'PRICE',
            'PURCHASE_PRICE',
        ];

        // результат для проверки
        $expectedResult = '(100 + (1.1 * {PURCHASE_PRICE}) + ({PRICE} * 1.2)) / 2 - 200';

        // заглушки

        // вычисление результата
        $object = new Formula();
        $object->setMarks($marks);
        $result = $object->cleanFormula($formula);

        // проверка
        $this->assertEquals($expectedResult, $result);
    }
}
