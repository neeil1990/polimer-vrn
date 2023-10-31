<?php
namespace Wbs24\Sbermmexport;

class StringTemplateTest extends BitrixTestCase
{
    public function test_getStringByTemplate()
    {
        // входные параметры
        $template = '{NAME} и {PROPERTY}';
        $markValues = [
            'NAME' => 'Название',
            'PROPERTY' => 'свойство',
        ];
        $defaultValue = 'Название';

        // результат для проверки
        $expectedResult = 'Название и свойство';

        // заглушка

        // вычисление результата
        $obj = new StringTemplate();
        $result = $obj->getStringByTemplate($template, $markValues, $defaultValue);

        // проверка
        $this->assertEquals($expectedResult, $result);
    }
}
