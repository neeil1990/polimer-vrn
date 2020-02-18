<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

if(!CModule::IncludeModule("iblock"))
	return;

$arComponentParameters = array(
	"GROUPS" => array(
	),
	"PARAMETERS" => array(
		"PRODUCT_ID" => Array(
			"PARENT" => "BASE",
			"NAME" => GetMessage("AG_ECD_PRODUCT_ID"),
			"TYPE" => "STRING",
			"DEFAULT" => "10",
		),

		"CACHE_TIME"  =>  Array("DEFAULT"=>3600),

	),
);
?>