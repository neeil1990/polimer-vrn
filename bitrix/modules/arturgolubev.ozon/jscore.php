<?
CJSCore::RegisterExt("ag_ozon_windows", Array(
	"lang" => "/bitrix/modules/arturgolubev.ozon/lang/ru/js/jscore_windows.php",
	"js" => array("/bitrix/js/arturgolubev.ozon/windows.js"),
	"css" => array("/bitrix/js/arturgolubev.ozon/css/windows.css", "/bitrix/js/arturgolubev.wildberries/font-awesome/css/font-awesome.min.css"),
));

CJSCore::RegisterExt("ag_ozon_options", Array(
	"lang" => "/bitrix/modules/arturgolubev.ozon/lang/ru/jscore_options.php",
	"css" => "/bitrix/js/arturgolubev.ozon/css/options.css",
	"js" => array("/bitrix/js/arturgolubev.ozon/options.js"),
	"rel" => array('jquery', 'ag_ozon_windows')
));

CJSCore::RegisterExt("ag_ozon_card", Array(
	"lang" => "/bitrix/modules/arturgolubev.ozon/lang/ru/jscore_card.php",
	"css" => "/bitrix/js/arturgolubev.ozon/css/card.css",
	"js" => "/bitrix/js/arturgolubev.ozon/card.js",
	"rel" => array('jquery', 'ag_ozon_windows')
));

CJSCore::RegisterExt("ag_ozon_order_tab", Array(
	"lang" => "/bitrix/modules/arturgolubev.ozon/lang/ru/jscore_order.php",
	"css" => "/bitrix/js/arturgolubev.ozon/css/order_tab.css",
	"js" => "/bitrix/js/arturgolubev.ozon/order_tab.js",
	"rel" => array('jquery', 'ag_ozon_windows')
));