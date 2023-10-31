<?php

if(count($arResult['PROPERTIES']['PRODUCT']['VALUE']) > 0 && is_numeric($arParams['CATALOG_IBLOCK_ID'])) {
    $arSelect = Array(
        "ID",
        "IBLOCK_ID",
        "NAME",
        "DETAIL_PAGE_URL",
        "PREVIEW_PICTURE",
        "PROPERTY_CML2_BASE_UNIT"
    );
    $arFilter = Array("IBLOCK_ID" => $arParams['CATALOG_IBLOCK_ID'], "ACTIVE" => "Y", "ID" => $arResult['PROPERTIES']['PRODUCT']['VALUE']);
    $res = CIBlockElement::GetList(Array(), $arFilter, false, false, $arSelect);
    while ($ob = $res->GetNextElement()) {
        $arFields = $ob->GetFields();
        $arProps = $ob->GetProperties();
        $arResult['ITEMS'][$arFields['ID']] = $arFields;
        $arResult['ITEMS'][$arFields['ID']]['PREVIEW_PICTURE'] = CFile::GetPath($arFields['PREVIEW_PICTURE']);
        $arResult['ITEMS'][$arFields['ID']]['PROPERTIES'] = $arProps;
        $arResult['SECTIONS'][$arFields['IBLOCK_SECTION_ID']]['ID'] = $arFields['IBLOCK_SECTION_ID'];
        $arResult['SECTIONS'][$arFields['IBLOCK_SECTION_ID']]['VALUE_XML_ID'] = $arProps['PROIZVODITEL']['VALUE_XML_ID'];
    }

    if(count($arResult['SECTIONS']) > 0){
        foreach($arResult['SECTIONS'] as &$arSection){
            $res = CIBlockSection::GetByID($arSection["ID"]);
            if($ar_res = $res->GetNext()){
                $ar_res['VALUE_XML_ID'] = $arSection['VALUE_XML_ID'];
                $arSection = $ar_res;
                $arSection['PICTURE'] = CFile::GetPath($arSection['PICTURE']);
            }
        }
    }
}
