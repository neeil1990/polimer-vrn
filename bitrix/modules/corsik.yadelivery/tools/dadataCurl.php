<?php
define('STOP_STATISTICS', true);
define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC', 'Y');
define('DisableEventsCheck', true);
define('BX_SECURITY_SHOW_MESSAGE', true);
define('NOT_CHECK_PERMISSIONS', true);

require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");

use Bitrix\Main\Loader;
use Bitrix\Main\Web\Json;

if (!Loader::includeModule('corsik.yadelivery'))
{
	return 'Module corsik.yadelivery not found';
}

global $APPLICATION;

$address = $APPLICATION->UnJSEscape(trim($_POST['address']));
$dadata = new Corsik\YaDelivery\Dadata();
$arResult = $dadata->clean('address', $address);

echo print Json::encode($arResult['suggestions'][0]);
