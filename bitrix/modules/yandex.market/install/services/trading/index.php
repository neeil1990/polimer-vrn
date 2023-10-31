<?php

use Bitrix\Main;

define('NO_AGENT_CHECK', true);
define('NO_AGENT_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
define('DisableEventsCheck', true);
define('BX_SECURITY_SESSION_VIRTUAL', true);

require_once $_SERVER['DOCUMENT_ROOT']. '/bitrix/modules/main/include/prolog_before.php';

$request = Main\Context::getCurrent()->getRequest();
$requestPage = $request->getRequestedPage();
$requestPage = preg_replace('#/index\.php$#', '/', $requestPage);
$sefFolder = BX_ROOT . '/services/yandex.market/trading/';
$serviceCode = null;
$behaviorCode = null;
$urlId = SITE_ID;
$reservedUrlIds = [
	'cart' => true,
	'order' => true,
	'stocks' => true,
];

if (preg_match('#^' . $sefFolder .'([\w\d-]+)(?:/|$)(?:([\w\d_-]{1,10})(?:/|$))?#', $requestPage, $matches))
{
	/** @var string[] $matches */
	$useMbstring = function_exists('mb_strrpos') && function_exists('mb_substr');
	$serviceCode = $matches[1];
	$sefFolder .= $serviceCode . '/';
	$behaviorPosition = $useMbstring
		? mb_strrpos($serviceCode, '-')
		: strrpos($serviceCode, '-');

	if ($behaviorPosition !== false)
	{
		$behaviorCode = $useMbstring
			? mb_substr($serviceCode, $behaviorPosition + 1)
			: substr($serviceCode, $behaviorPosition + 1);
		$serviceCode = $useMbstring
			? mb_substr($serviceCode, 0, $behaviorPosition)
			: substr($serviceCode, 0, $behaviorPosition);
	}

	if (isset($matches[2]) && !isset($reservedUrlIds[$matches[2]]))
	{
		$urlId = $matches[2];
		$sefFolder .= $urlId . '/';
	}
}

$APPLICATION->IncludeComponent('yandex.market:purchase', '', [
	'SEF_FOLDER' => $sefFolder,
	'SERVICE_CODE' => $serviceCode,
	'BEHAVIOR_CODE' => $behaviorCode,
	'URL_ID' => $urlId,
], false, [ 'HIDE_ICONS' => 'Y' ]);

require_once $_SERVER['DOCUMENT_ROOT']. '/bitrix/modules/main/include/epilog_after.php';