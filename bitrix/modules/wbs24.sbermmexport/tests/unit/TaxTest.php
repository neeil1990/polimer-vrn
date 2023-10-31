<?php
namespace Wbs24\Sbermmexport;

class TaxTest extends BitrixTestCase
{
    public function testGetVat()
    {
        // входные параметры
        $param = [
            'vatEnable' => true,
            'vatBase' => '18%',
            'vatList' => [
                0 => 'NO_VAT',
                1 => 'VAT_0',
                2 => 'VAT_10',
                3 => 'VAT_18',
            ],
        ];

        $vatIds = [0, 1, 2, 3];
        $items = [];
        foreach ($vatIds as $vatId) {
            $items[] = [
                'VAT_ID' => $vatId,
            ];
        }

        // результат для проверки
        $expectedResultArray = [
            'VAT_18',
            'VAT_0',
            'VAT_10',
            'VAT_18',
        ];

        // заглушка

        // обход условий
        $taxObject = new Tax($param);
        foreach ($items as $k => $item) {
            // вычисление результата
            $result = $taxObject->getVat($item);

            // проверка
            $this->assertEquals($expectedResultArray[$k], $result);
        }
    }

    public function testGetVatDisabled()
    {
        // входные параметры
        $param = [
            'vatEnable' => false,
            'vatBase' => '18%',
            'vatList' => [
                0 => 'NO_VAT',
                1 => 'VAT_0',
                2 => 'VAT_10',
                3 => 'VAT_18',
            ],
        ];

        $vatIds = [0, 1, 2, 3];
        $items = [];
        foreach ($vatIds as $vatId) {
            $items[] = [
                'VAT_ID' => $vatId,
            ];
        }

        // результат для проверки
        $expectedResult = false;

        // заглушка

        // обход условий
        $taxObject = new Tax($param);
        foreach ($items as $k => $item) {
            // вычисление результата
            $result = $taxObject->getVat($item);

            // проверка
            $this->assertEquals($expectedResult, $result);
        }
    }
}
