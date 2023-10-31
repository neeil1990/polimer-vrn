<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
$arParameters = Array(
	"USER_PARAMETERS" => Array(),
);

/* $arParameters["USER_PARAMETERS"]["EXTENDED_MODE"] = array(
	"NAME" => GetMessage("ARTURGOLUBEV_WATCHER_PARAM_EXTENDED_MODE"),
	"TYPE" => "CHECKBOX",
	"MULTIPLE" => "N",
	"DEFAULT" => "N",
);

$days = array(
	"1" => GetMessage("ARTURGOLUBEV_WATCHER_PARAM_DAYS_TODAY"),
	"2" => GetMessage("ARTURGOLUBEV_WATCHER_PARAM_DAYS_YESTERDAY"),
	"3" => GetMessage("ARTURGOLUBEV_WATCHER_PARAM_DAYS_THREE"),
	"7" => GetMessage("ARTURGOLUBEV_WATCHER_PARAM_DAYS_SEVEN"),
);
$arParameters["USER_PARAMETERS"]["DAYS"] = array(
	"NAME" => GetMessage("ARTURGOLUBEV_WATCHER_PARAM_DAYS"),
	"TYPE" => "LIST",
	"MULTIPLE" => "N",
	"DEFAULT" => "1",
	"VALUES" => $days
); */


$show_targets = array(
	"" => GetMessage("ARTURGOLUBEV_WATCHER_PARAM_SHOW_TARGET_AG"),
	"all" => GetMessage("ARTURGOLUBEV_WATCHER_PARAM_SHOW_TARGET_ALL"),
);
$arParameters["USER_PARAMETERS"]["SHOW_TARGET"] = array(
	"NAME" => GetMessage("ARTURGOLUBEV_WATCHER_PARAM_SHOW_TARGET"),
	"TYPE" => "LIST",
	"MULTIPLE" => "N",
	"DEFAULT" => "",
	"VALUES" => $show_targets
);
?>
