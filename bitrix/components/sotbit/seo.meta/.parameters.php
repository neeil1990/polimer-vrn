<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
if (!CModule::IncludeModule("iblock"))
	return;
$arIBlockType = CIBlockParameters::GetIBlockTypes();
$rsIBlock = CIBlock::GetList(
    array(
	    "sort" => "asc",
    ),
    array(
        "TYPE" => $arCurrentValues["IBLOCK_TYPE"],
        "ACTIVE" => "Y",
    )
);

while ($arr = $rsIBlock->Fetch())
	$arIBlock[$arr["ID"]] = "[".$arr["ID"]."] ".$arr["NAME"];

$arComponentParameters = array(
    "GROUPS" => array(),
    "PARAMETERS" => array(
        "FILTER_NAME" => array(
            "PARENT" => "DATA_SOURCE",
            "NAME" => GetMessage("SM_FILTER_NAME"),
            "TYPE" => "STRING",
            "DEFAULT" => '',
        ),
        "SECTION_ID" => array(
            "PARENT" => "DATA_SOURCE",
            "NAME" => GetMessage("SM_SECTION_ID"),
            "TYPE" => "STRING",
            "DEFAULT" => '={$_REQUEST["SECTION_ID"]}',
        ),
        "CACHE_TIME" => array(
            "DEFAULT" => 36000000,
        ),
        "KOMBOX_FILTER" => array(
            'NAME' => GetMessage('USE_KOMBOX_FILTER'),
            'TYPE' => 'CHECKBOX',
            'DEFAULT' => 'N',
            'REFRESH' => 'Y',
        ),
    ),
);   
?>