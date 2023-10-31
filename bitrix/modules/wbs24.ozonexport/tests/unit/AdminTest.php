<?php
namespace Wbs24\Ozonexport;

use Bitrix\Main\Loader;

class AdminTest extends BitrixTestCase
{
    public function testGetSelectForPriceTypes()
    {
        Loader::includeModule('iblock');
        Loader::includeModule('catalog');

        // входные параметры
        $iblockId = 1;
        $field = 'SET_OFFER_ID';
        $currentValue = 'ARTNUMBER';

        // результат для проверки
        $expectedResult =
            '<select name="BLOB[priceType]">'
                .'<option value="">Default</option>'
                .'<option value="1">[1] (Base)</option>'
                .'<option value="2" selected>[2] Recommend price (Recommend)</option>'
            .'</select>'
        ;

        // заглушка
		$CIBlockResultStub = $this->createMock(\CIBlockResult::class);
		$fetchResults[] = [
            'ID' => 1,
            'NAME_LANG' => '',
            'NAME' => 'Base',
		];
		$fetchResults[] = [
            'ID' => 2,
            'NAME_LANG' => 'Recommend price',
            'NAME' => 'Recommend',
		];
		$fetchResults[] = false;
		$CIBlockResultStub->method('Fetch')
			->will($this->onConsecutiveCalls(...$fetchResults));

        $CCatalogGroupStub = $this->createMock(CCatalogGroup::class);
        $CCatalogGroupStub->method('GetListEx')
            ->willReturn($CIBlockResultStub);

        // вычисление результата
        $admin = new Admin([
            'objects' => [
                'CCatalogGroup' => $CCatalogGroupStub,
            ],
        ]);
        $result = $admin->getSelectForPriceTypes(2, 'Default');

        // проверка
        $this->assertEquals($expectedResult, $result);
    }

    public function testGetSelectForOfferId()
    {
        Loader::includeModule('iblock');

        // входные параметры
        $iblockId = 1;
        $field = 'SET_OFFER_ID';
        $currentValue = 'ARTNUMBER';

        // результат для проверки
        $expectedResult =
            '<select name="SET_OFFER_ID">'
                .'<option  value="ID" data-selected="N">ID</option>'
                .'<option  value="XML_ID" data-selected="N">XML_ID (Внешний код)</option>'
                .'<option data-iblock-id="2" value="ARTNUMBER" selected data-selected="Y">Артикул</option>'
            .'</select>'
            .'<script>document.addEventListener("DOMContentLoaded", function () {let ozon = new Wbs24Ozonexport();ozon.activateOptionsForCurrentIblock("SET_OFFER_ID", 2);});</script>'
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
