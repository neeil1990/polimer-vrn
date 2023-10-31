<?php                                                                    
require_once ($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/main/include/prolog_admin_before.php");
use Bitrix\Main\Localization\Loc;
use Sotbit\Seometa\SeometaUrlTable;
use Bitrix\Main\Loader;
use Bitrix\Main\Type;

$moduleId = "sotbit.seometa";

if (
    !Loader::includeModule($moduleId) ||
    $APPLICATION->GetGroupRight($moduleId) == 'D'
) {
    $APPLICATION->AuthForm(Loc::getMessage("ACCESS_DENIED"));
}

Loader::includeModule( 'iblock' );

$chpuAll = SeometaUrlTable::GetList(['filter' => ['!=PROPERTIES' => null]]);
while ($chpu = $chpuAll->fetch()) {
    $cond_properties = unserialize($chpu['PROPERTIES']);   
    $arFilter = [
        'ACTIVE' => 'Y',        
        'INCLUDE_SUBSECTIONS' => 'Y',        
        'IBLOCK_ID' => $chpu['iblock_id'],
        'SECTION_ID' => $chpu['section_id'],
    ];
    foreach($cond_properties as $code => $vals){
        if ($code != 'PRICE') {
            $filter = ['CODE' => $code];
            if (intval($code)) {
                $filter = ['ID' => $code];
            }

            $pr = \CIBlockProperty::GetList([], $filter)->fetch();
            if ($pr['PROPERTY_TYPE'] != 'L' && $pr['PROPERTY_TYPE'] != 'E') {
                $arFilter['PROPERTY_' . $pr['ID']] = $vals;
            } else {
                $arFilter['PROPERTY_' . $pr['ID'] . '_VALUE'] = $vals;
            }
        } else {
            foreach ($vals as $price_code => $price) {
                if (isset($price['FROM']) && $price['FROM'] !== '') {
                    $arFilter['>=CATALOG_PRICE_' . $price_code] = $price['FROM'];
                }

                if (isset($price['TO']) && $price['TO'] !== '') {
                    $arFilter['<=CATALOG_PRICE_' . $price_code] = $price['TO'];
                }
            }
        }
    }

    $count = \CIBlockElement::GetList([],$arFilter)->SelectedRowsCount();
    SeometaUrlTable::Update(
        $chpu['ID'],
        [
            'PRODUCT_COUNT' => $count,
            'DATE_CHANGE' => new Type\DateTime(date('Y-m-d H:i:s'), 'Y-m-d H:i:s')
        ]
    );
    unset($chpu);
}

unset($chpuAll);
?>       
