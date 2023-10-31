<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
if (!CModule::IncludeModule("iblock"))
	return;
$arIBlockType = CIBlockParameters::GetIBlockTypes();
$rsIBlock = CIBlock::GetList(array(
	"sort" => "asc",
), array(
	"TYPE" => $arCurrentValues["IBLOCK_TYPE"],
	"ACTIVE" => "Y",
));
while ($arr = $rsIBlock->Fetch())
	$arIBlock[$arr["ID"]] = "[".$arr["ID"]."] ".$arr["NAME"];

	$result =\Bitrix\Iblock\SectionTable::getList(array( 
		'select' => array('ID','NAME'), 
		'filter' => array('IBLOCK_ID' => $arCurrentValues['IBLOCK_ID']) 
	)); 
	while ($Section = $result->fetch()) 
	{ 
		$arSections[$Section["ID"]] = "[".$Section["ID"]."] ".$Section["NAME"];
	} 
	
$arComponentParameters = array(
"GROUPS" => array(),
    "PARAMETERS" => array(
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
        "SECTION_ID" => array(
            "PARENT" => "BASE",
            "NAME" => GetMessage("SM_SECTION_ID"),
            "TYPE" => "LIST",
            "ADDITIONAL_VALUES" => "Y",
                "VALUES" => $arSections,
        ),
        "INCLUDE_SUBSECTIONS" => array(
                "PARENT" => "BASE",
            'NAME' => GetMessage('SM_INCLUDE_SUBSECTIONS'),
                "TYPE" => "LIST",
                "VALUES" => array(
                        "Y" => GetMessage('SM_INCLUDE_SUBSECTIONS_ALL'),
                        "A" => GetMessage('SM_INCLUDE_SUBSECTIONS_ACTIVE'),
                        "N" => GetMessage('SM_INCLUDE_SUBSECTIONS_NO'),
                ),
                "DEFAULT" => "Y",
        ),
        'SORT' => array(
                "PARENT" => "BASE",
            'NAME' => GetMessage('SM_SORT'),
            'TYPE' => "LIST",
            "VALUES" => array(
                'NAME' => GetMessage("SM_SORT_NAME"),
                'CONDITIONS' => GetMessage("SM_SORT_CONDITION"),
                'URL_SORT' => GetMessage("SM_SORT_URL_SORT"),
                'PRODUCT_COUNT' => GetMessage('SM_SORT_PRODUCT_COUNT'),
                'RANDOM' => GetMessage("SM_SORT_RANDOM")
            ),
        ),
        'SORT_ORDER' => array(
                "PARENT" => "BASE",
            'NAME' => GetMessage('SM_SORT_ORDER'),
            'TYPE' => "LIST",
            "VALUES" => array('asc'=>GetMessage("SM_SORT_ORDER_ASC"),'desc'=>GetMessage("SM_SORT_ORDER_DESC")),
        ),
        'CNT_TAGS' => array(
            "PARENT" => "BASE",
            "NAME" => GetMessage("SM_CNT_TAGS"),
            "TYPE" => "STRING",
            "DEFAULT" => '',
        ),
        "PRODUCT_COUNT" => array(
            "PARENT" => "BASE",
            "NAME" => GetMessage("SM_PRODUCT_COUNT"),
            "TYPE" => "CHECKBOX",
            "DEFAULT" => "N",
        ),
        "GENERATING_TAGS" => array(
            "PARENT" => "BASE",
            "NAME" => GetMessage("SM_GENERATING_TAGS"),
            "TYPE" => "CHECKBOX",
            "DEFAULT" => "N",
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
    ),
);   
?>