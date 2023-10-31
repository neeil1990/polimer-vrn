<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
if (!CModule::IncludeModule("iblock"))
    return;

// Main params
$arIBlockType = CIBlockParameters::GetIBlockTypes();
$rsIBlock = CIBlock::GetList(array(
    "sort" => "asc",
), array(
    "TYPE" => $arCurrentValues["IBLOCK_TYPE"],
    "ACTIVE" => "Y",
));
while ($arr = $rsIBlock->Fetch())
    $arIBlock[$arr["ID"]] = "[".$arr["ID"]."] ".$arr["NAME"];

$arPropList = [];
if(intval($arCurrentValues['IBLOCK_ID']) > 0) {
    $rsProps = CIBlockProperty::GetList(
        array(),
        array(
            'IBLOCK_ID' => $arCurrentValues['IBLOCK_ID']
        )
    );

    while ($arProp = $rsProps->Fetch()) {
        $arPropList[$arProp['ID']] = '[' . $arProp['ID'] . '] ' . $arProp['NAME'];
    }

    if(CModule::IncludeModule('catalog')) {
        $offerBlock = CCatalog::GetList(
            array(),
            array(
                'IBLOCK_ID' => $arCurrentValues['IBLOCK_ID']
            ),
            false,
            false,
            array('OFFERS_IBLOCK_ID')
        )->fetch();

        if($offerBlock) {
            $rsSku = CIBlockProperty::GetList(
                array(),
                array(
                    'IBLOCK_ID' => $offerBlock['OFFERS_IBLOCK_ID']
                )
            );

            while ($prop = $rsSku->fetch()) {
                $arPropList[$prop['ID']] = '[' . $prop['ID'] . '] ' . $prop['NAME'];
            }
        }
    }
}

// Brands params
$arIBlockTypeBrands = CIBlockParameters::GetIBlockTypes();
$rsIBlock = CIBlock::GetList(
    array(
        "sort" => "asc",
    ),
    array(
        "TYPE" => $arCurrentValues["BRAND_IBLOCK_TYPE"],
        "ACTIVE" => "Y",
    )
);
while ($arr = $rsIBlock->Fetch()) {
    $arIBlockBrands[$arr["ID"]] = "[" . $arr["ID"] . "] " . $arr["NAME"];
}

if(intval($arCurrentValues['BRAND_IBLOCK_ID']) > 0) {
    $rsBrands = CIBlockProperty::GetList(
        array(),
        array(
            'IBLOCK_ID' => $arCurrentValues['BRAND_IBLOCK_ID']
        )
    );

    while ($arBrand = $rsBrands->Fetch()) {
        $arBrandsList[$arBrand['ID']] = $arBrand['NAME'];
    }

    if(CModule::IncludeModule('catalog')) {
        $offerBlock = CCatalog::GetList(
            array(),
            array(
                'IBLOCK_ID' => $arCurrentValues['BRAND_IBLOCK_ID']
            ),
            false,
            false,
            array('OFFERS_IBLOCK_ID')
        )->fetch();

        if($offerBlock) {
            $rsBrands = CIBlockProperty::GetList(
                array(),
                array(
                    'IBLOCK_ID' => $offerBlock['OFFERS_IBLOCK_ID']
                )
            );

            while ($arBrand = $rsBrands->Fetch()) {
                $arBrandsList[$arBrand['ID']] = $arBrand['NAME'];
            }
        }
    }
}

$arComponentParameters = array(
    "GROUPS" => array(
        'BRAND_PARAMETERS' => array(
            'NAME' => GetMessage('SM_BRAND_PARAMS_NAME'),
            'SORT' => 150
        )
    ),
    "PARAMETERS" => array(
        'TAB_NAME' => array(
            "PARENT" => "BASE",
            "NAME" => GetMessage('SM_TAB_PROPERTY_NAME'),
            "TYPE" => "STRING",
            "DEFAULT" => GetMessage('SM_TAB_PROPERTY_NAME_DEFAULT'),
        ),
        "IBLOCK_TYPE" => array(
            "PARENT" => "BASE",
            "NAME" => GetMessage("SM_IBLOCK_TYPE"),
            "TYPE" => "LIST",
            "VALUES" => $arIBlockType,
            "REFRESH" => "Y",
        ),
        "IBLOCK_ID" => array(
            "PARENT" => "BASE",
            "NAME" => GetMessage("SM_IBLOCK_IBLOCK"),
            "TYPE" => "LIST",
            "ADDITIONAL_VALUES" => "Y",
            "VALUES" => $arIBlock,
            "REFRESH" => "Y",
        ),
        "EXCLUDE_PROPERTY_LIST" => array(
            "PARENT" => "BASE",
            "NAME" => GetMessage("SM_EXCLUDE_PROPERTY_LIST"),
            "TYPE" => "LIST",
            "MULTIPLE" => "Y",
            "SIZE" => 10,
            "VALUES" => $arPropList,
        ),
        "SHOW_BRAND_TAB" => array(
            "PARENT" => "BRAND_PARAMETERS",
            "NAME" => GetMessage("SM_SHOW_BRAND_TAB"),
            "TYPE" => "CHECKBOX",
            "DEFAULT" => "N",
            "REFRESH" => "Y",
        ),
        "CACHE_TIME" => array(
            "DEFAULT" => 36000000,
        ),
        "CACHE_GROUPS" => array(
            "PARENT" => "CACHE_SETTINGS",
            "NAME" => GetMessage("SM_CACHE_GROUPS"),
            "TYPE" => "CHECKBOX",
            "DEFAULT" => "Y",
        ),
    )
);

if($arCurrentValues['SHOW_BRAND_TAB'] == 'Y') {
    $arBrandProps = array(
        'BRAND_TAB_NAME' => array(
            "PARENT" => "BRAND_PARAMETERS",
            "NAME" => GetMessage('SM_BRAND_TAB_NAME'),
            "TYPE" => "STRING",
            "DEFAULT" => GetMessage('SM_BRAND_TAB_NAME_DEFAULT'),
        ),
        "BRAND_IBLOCK_TYPE" => array(
            "PARENT" => "BRAND_PARAMETERS",
            "NAME" => GetMessage("SM_IBLOCK_TYPE"),
            "TYPE" => "LIST",
            "VALUES" => $arIBlockTypeBrands,
            "REFRESH" => "Y",
        ),
        "BRAND_IBLOCK_ID" => array(
            "PARENT" => "BRAND_PARAMETERS",
            "NAME" => GetMessage("SM_IBLOCK_IBLOCK"),
            "TYPE" => "LIST",
            "ADDITIONAL_VALUES" => "Y",
            "VALUES" => $arIBlockBrands,
            "REFRESH" => "Y",
        ),
        "BRANDS_LIST" => array(
            "PARENT" => "BRAND_PARAMETERS",
            "NAME" => GetMessage("SM_EXCLUDE_PROPERTY_LIST"),
            "TYPE" => "LIST",
            "VALUES" => $arBrandsList,
        ),
    );

    $arComponentParameters['PARAMETERS'] = array_merge($arComponentParameters['PARAMETERS'], $arBrandProps);
}
?>