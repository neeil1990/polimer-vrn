<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

if(!CModule::IncludeModule("iblock"))
	return;

$module_name = 'arturgolubev.ozon';

$accounts = [];

if(CModule::IncludeModule($module_name)){
	$arIntegrationList = \Arturgolubev\Ozon\Tools::getMenuIntegrationsList();
	foreach($arIntegrationList as $integration){
		if($integration['token']){
			$accounts[$integration['ID']] = ($integration['custom_name']) ? $integration['custom_name'] : $integration["ID"];
		}
	}
}

if(!count($accounts)){
	$accounts[] = GetMessage("ARTURGOLUBEV_OZON_COMPONENT_PARAM_OZON_ACCOUNT_BASE");
}

$arComponentParameters = array(
	"GROUPS" => [],
	"PARAMETERS" => [
		"OZON_ACCOUNT" => [
			"PARENT" => "BASE",
			"NAME" => GetMessage("ARTURGOLUBEV_OZON_COMPONENT_PARAM_OZON_ACCOUNT"),
			"TYPE" => "LIST",
			"VALUES" => $accounts,
			"DEFAULT" => "",
		],
		"ELEMENT_ID" => [
			"PARENT" => "BASE",
			"NAME" => GetMessage("ARTURGOLUBEV_OZON_COMPONENT_PARAM_ELEMENT_ID"),
			"TYPE" => "STRING",
			"DEFAULT" => "",
		],
		"TEXT_BEFORE_BTN" => [
			"PARENT" => "BASE",
			"NAME" => GetMessage("ARTURGOLUBEV_OZON_COMPONENT_PARAM_TEXT_BEFORE_BTN"),
			"TYPE" => "STRING",
			"DEFAULT" => GetMessage("ARTURGOLUBEV_OZON_COMPONENT_PARAM_TEXT_BEFORE_BTN_DEFAULT"),
		],
		"TEXT_BTN" => [
			"PARENT" => "BASE",
			"NAME" => GetMessage("ARTURGOLUBEV_OZON_COMPONENT_PARAM_TEXT_BTN"),
			"TYPE" => "STRING",
			"DEFAULT" => GetMessage("ARTURGOLUBEV_OZON_COMPONENT_PARAM_TEXT_BTN_DEFAULT"),
		],

		"CACHE_TIME" => Array("DEFAULT" => 360000),
	],
);
?>