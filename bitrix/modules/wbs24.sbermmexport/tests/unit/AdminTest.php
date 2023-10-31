<?php
namespace Wbs24\Sbermmexport;

use Bitrix\Main\Loader;

class AdminTest extends BitrixTestCase
{
    public function testGetSelectForOfferId()
    {
        Loader::includeModule('iblock');

        // входные параметры
        $iblockId = 1;
        $field = 'SET_OFFER_ID';
        $currentValue = 'ARTNUMBER';

        // результат для проверки
        $expectedResult =
            '<select data-prop="SET_OFFER_ID" name="SET_OFFER_ID">'
                .'<option  value="ID" data-selected="N">ID</option>'
                .'<option  value="XML_ID" data-selected="N">XML_ID (Внешний код)</option>'
                .'<option data-iblock-id="2" value="ARTNUMBER" selected data-selected="Y">Артикул</option>'
            .'</select>'
            .'<script>document.addEventListener("DOMContentLoaded", function () {let sbermm = new Wbs24Sbermmexport();sbermm.activateOptionsForCurrentIblock("SET_OFFER_ID", 2);});</script>'
        ;

        // заглушка
        $CCatalogStub = $this->createMock(CCatalog::class);
        $CCatalogStub->method('GetByIDExt')
            ->willReturn([
                'OFFERS_IBLOCK_ID' => 2,
            ]);

        $CIBlockResultStub = $this->createMock(\CIBlockResult::class);
        $fetchResults[] = [
            'ID' => 3,
            'NAME' => 'Артикул',
            'CODE' => 'ARTNUMBER',
            'IBLOCK_ID' => 2,
            'PROPERTY_TYPE' => 'S',
        ];
        $fetchResults[] = false;
        $CIBlockResultStub->method('Fetch')
            ->will($this->onConsecutiveCalls(...$fetchResults));

        $CIBlockPropertyStub = $this->createMock(CIBlockProperty::class);
        $CIBlockPropertyStub->method('GetList')
            ->willReturn($CIBlockResultStub);

        // вычисление результата
        $admin = new Admin([
            'objects' => [
                'CCatalog' => $CCatalogStub,
                'CIBlockProperty' => $CIBlockPropertyStub,
            ],
        ]);
        $result = $admin->getSelectForOfferId($iblockId, $field, $currentValue);

        // проверка
        $this->assertEquals($expectedResult, $result);
    }
}
