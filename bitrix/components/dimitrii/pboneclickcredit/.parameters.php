<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
if (CModule::IncludeModule("iblock") && CModule::IncludeModule("sale") && CModule::IncludeModule("catalog") && CModule::IncludeModule("currency")) {
    $arIBlocks = Array();
    if ($arCurrentValues["IBLOCK_TYPE"] != "-") {
        $res = CIBlock::GetList(Array("SORT" => "ASC"), Array("SITE_ID" => $_REQUEST["site"], "TYPE" => $arCurrentValues["IBLOCK_TYPE"]));
        while ($arRes = $res->Fetch()) {
            $arIBlocks[$arRes["ID"]] = '[' . $arRes["ID"] . ']' . $arRes["NAME"];
        }
    }

    $arPersonTypes = array();
    $res = CSalePersonType::GetList(array('ID' => 'ASC'), array('LID' => $_REQUEST["site"]));
    while ($arRes = $res->Fetch()) {
        $arPersonTypes[$arRes['ID']] = '[' . $arRes['ID'] . '] ' . $arRes['NAME'];
    }

    $arPayments = array('0' => GetMessage('NOT_SET'));
    $res = CSalePaySystem::GetList(array('SORT' => 'ASC'), array('ACTIVE' => 'Y', 'LID' => $_REQUEST["site"]), false, false, array('ID', 'NAME'));
    while ($arRes = $res->Fetch()) {
        $arPayments[$arRes["ID"]] = '[' . $arRes["ID"] . ']' . $arRes["NAME"];
    }

    $arCurrencies = array();
    $res = CCurrency::GetList(($b = 'name'), ($o = 'asc'));
    while ($arRes = $res->Fetch()) {
        $arCurrencies[$arRes['CURRENCY']] = '[' . $arRes['CURRENCY'] . '] ' . $arRes['FULL_NAME'];
    }
    $default_currency = COption::GetOptionString('sale', 'default_currency', 'RUB');

    $arPrices = array();
    $res = CCatalogGroup::GetList(array('SORT' => 'ASC'));
    while ($arRes = $res->Fetch()) {
        $arPrices[$arRes['NAME']] = '[' . $arRes['ID'] . '] ' . $arRes['NAME'] . ' ' . ($arRes['BASE'] == 'Y' ? GetMessage('BASE_PRICE') : '');
    }

    $arComponentParameters = array(
        "GROUPS" => array(
            "SKU_PROPERTIES" => array("NAME" => GetMessage("GROUP_SKU_PROPERTIES"),),
        ),
        "PARAMETERS" => array(
            "IBLOCK_TYPE" => Array(
                "PARENT" => "BASE",
                "NAME" => GetMessage("PARAMETER_IBLOCK_TYPE"),
                "TYPE" => "LIST",
                "VALUES" => CIBlockParameters::GetIBlockTypes(),
                "DEFAULT" => "",
                "REFRESH" => "Y",
            ),
            "IBLOCK_ID" => Array(
                "PARENT" => "BASE",
                "NAME" => GetMessage("PARAMETER_IBLOCK_ID"),
                "TYPE" => "LIST",
                "VALUES" => $arIBlocks,
                "DEFAULT" => '',
                "ADDITIONAL_VALUES" => "N",
                "REFRESH" => "Y",
            ),
            "ELEMENT_ID" => array(
                "PARENT" => "BASE",
                "NAME" => GetMessage("PARAMETER_ELEMENT_ID"),
                "TYPE" => "STRING",
                "DEFAULT" => '={$_REQUEST["ELEMENT_ID"]}',
            ),
            "DEFAULT_PERSON_TYPE" => array(
                "PARENT" => "BASE",
                "NAME" => GetMessage("PARAMETER_DEFAULT_PERSON_TYPE"),
                "TYPE" => "LIST",
                "VALUES" => $arPersonTypes,
                "ADDITIONAL_VALUES" => "N",
                "REFRESH" => "N",
                "MULTIPLE" => "N",
                "DEFAULT" => 1,
            ),
            "DEFAULT_PAYMENT" => array(
                "PARENT" => "BASE",
                "NAME" => GetMessage("PARAMETER_DEFAULT_PAYMENT"),
                "TYPE" => "LIST",
                "VALUES" => $arPayments,
                "ADDITIONAL_VALUES" => "N",
                "REFRESH" => "N",
                "MULTIPLE" => "N",
            ),
            "DEFAULT_CURRENCY" => array(
                "PARENT" => "BASE",
                "NAME" => GetMessage("PARAMETER_DEFAULT_CURRENCY"),
                "TYPE" => "LIST",
                "VALUES" => $arCurrencies,
                "ADDITIONAL_VALUES" => "N",
                "DEFAULT" => $default_currency,
                "REFRESH" => "N",
                "MULTIPLE" => "N",
            ),
            "PRICE_ID" => array(
                "PARENT" => "BASE",
                "NAME" => GetMessage("PARAMETER_PRICE_ID"),
                "TYPE" => "LIST",
                "VALUES" => $arPrices,
                "DEFAULT" => "BASE",
            ),

            "CREDIT_BTN_CLASS" => array(
                "PARENT" => "BASE",
                "NAME" => GetMessage("PARAMETER_CREDIT_BTN_CLASS"),
                "TYPE" => "STRING",
                "DEFAULT" => 'btn btn-default',
            ),
            "CREDIT_BTN_NAME" => array(
                "PARENT" => "BASE",
                "NAME" => GetMessage("PARAMETER_CREDIT_BTN_NAME"),
                "TYPE" => "STRING",
                "DEFAULT" => GetMessage("PARAMETER_CREDIT_BTN_NAME_DEF"),
            ),
        ),
    );

}