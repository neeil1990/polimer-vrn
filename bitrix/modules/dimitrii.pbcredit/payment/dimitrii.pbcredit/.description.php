<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die(); ?><?
include(GetLangFileName(dirname(__FILE__) . "/", "/pochtabank.php"));

$psTitle = GetMessage("DI_PB_TITLE");
$psDescription = GetMessage("DI_PB_DESCRIPTION");

$arPSCorrespondence = array(
    "ORDER_ID" => array(
        "NAME" => GetMessage("DI_PB_ORDER_ID"),
        "DESCR" => "",
        "VALUE" => "ID",
        "TYPE" => "ORDER"
    ),
    "KEY" => array(
        "NAME" => GetMessage("DI_PB_KEY"),
        "DESCR" => GetMessage("DI_PB_KEY_DESC"),
        "VALUE" => "",
        "TYPE" => ""
    ),
    "NAME" => array(
        "NAME" => GetMessage("DI_PB_NAME"),
        "DESCR" => GetMessage("DI_PB_NAME_DESC"),
        "VALUE" => "",
        "TYPE" => ""
    ),

    "CATEGORY" => array(
        "NAME" => GetMessage("DI_PB_CATEGORY"),
        "DESCR" => GetMessage("DI_PB_CATEGORY"),
        "VALUE" => "",
        "TYPE" => ""
    ),

);
?>
