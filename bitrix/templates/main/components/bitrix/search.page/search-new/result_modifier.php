<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

global $USER;
CModule::IncludeModule('iblock');
CModule::IncludeModule('catalog');
$groups = $USER->GetUserGroupArray();

foreach($groups as $group){
    $obPriceTypes = CCatalogGroup::GetGroupsList( array(
        'GROUP_ID' => $group
    ) );//get user price types

    while($type = $obPriceTypes->GetNext())
    {
        if($type['BUY'] != 'Y')
            continue;
        $priceTypes[$type['CATALOG_GROUP_ID']] = $type['CATALOG_GROUP_ID'];
    }
}

$select = array(
    'IBLOCK_ID',
    'IBLOCK_SECTION_ID',
    'ID',
    'PREVIEW_PICTURE',
    'DETAIL_PICTURE'
);

foreach($priceTypes as $type)
{
    $select[] = 'CATALOG_GROUP_' . $type;
}

$arSection = array();

if($arResult["NAV_RESULT"])
	$arResult['ROWS_COUNT'] = $arResult["NAV_RESULT"]->SelectedRowsCount();
	
foreach($arResult['SEARCH'] as &$arItem)
{
    $arSelect = Array("ID", "NAME", "DATE_ACTIVE_FROM", "PREVIEW_PICTURE", "CATALOG_QUANTITY", "IBLOCK_SECTION_ID", "DETAIL_PAGE_URL", "PROPERTY_CML2_BASE_UNIT");
    $arFilter = Array("IBLOCK_ID" => $arItem['PARAM2'],"ID" => $arItem['ITEM_ID']);
    $res = CIBlockElement::GetList(Array(), $arFilter, false, false, $arSelect);
    if($ob = $res->GetNextElement()){
        $arItem = $ob->GetFields();
        $arSection[] = $arItem['IBLOCK_SECTION_ID'];
        $IBLOCK_ID = $arItem['PARAM2'];
    }
}

if($arSection){
    $arSection = array_unique($arSection);
    $arFilter = Array('IBLOCK_ID' => $IBLOCK_ID, 'ID' => $arSection);
    $db_list = CIBlockSection::GetList(Array($by => $order), $arFilter, true);
    while($ar_result = $db_list->GetNext())
    {
        $arResult['SECTIONS'][] = $ar_result;
    }
}

