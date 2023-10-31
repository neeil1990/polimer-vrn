<?php

use Bitrix\Main\Loader;
use Bitrix\Main\Web\Json;
use Corsik\YaDelivery\Table\ZonesTable;

require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");

global $APPLICATION;

$coordinates = [];
if (Loader::includeModule('corsik.yadelivery'))
{
	$ID = $APPLICATION->UnJSEscape($_GET['ID']);
	$queryObject = ZonesTable::getById($ID)->fetch();
	$coordinates = Json::encode($queryObject['COORDINATES']);
}

print_r($coordinates);
