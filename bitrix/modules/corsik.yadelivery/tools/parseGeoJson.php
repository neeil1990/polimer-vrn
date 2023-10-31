<?php

use Bitrix\Main\Loader;

require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");

$coords = [];
if (Loader::includeModule('corsik.yadelivery'))
{
	$coords = Corsik\YaDelivery\Handler::changeLatLong($_REQUEST['geoJson'], $_REQUEST['type'], true);
}

print_r($coords);
