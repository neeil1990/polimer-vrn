<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$arComponentDescription = array(
	"NAME" => GetMessage("ARTURGOLUBEV_OZON_COMPONENT_CHECK_NAME"),
	"DESCRIPTION" => GetMessage("ARTURGOLUBEV_OZON_COMPONENT_CHECK_DESCRIPTION"),
	"ICON" => "/images/icon.gif",
	"SORT" => 510,
	"CACHE_PATH" => "Y",
	"PATH" => array(
		"ID" => "AG_DOP_SERVICES",
		"NAME" => GetMessage("ARTURGOLUBEV_OZON_MAIN_FOLDER"),
		"SORT" => 1930,
		"CHILD" => array(
			"ID" => "OZON",
			"NAME" => GetMessage("ARTURGOLUBEV_OZON_TITLE_FOLDER"),
			"SORT" => 140
		)
	),
	"COMPLEX" => "N",
);
?>