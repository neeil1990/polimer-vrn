<?php

define('STOP_STATISTICS', true);
define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC', 'Y');
define('DisableEventsCheck', true);
define('BX_SECURITY_SHOW_MESSAGE', true);
define('NOT_CHECK_PERMISSIONS', true);

use Bitrix\Main\Loader;
use Bitrix\Main\Web\Json;
use Bitrix\Main\Web\PostDecodeFilter;
use Corsik\YaDelivery\Core;

require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php');

$request = Bitrix\Main\Application::getInstance()->getContext()->getRequest();
$request->addFilter(new PostDecodeFilter);
$post = $request->getPostList()->toArray();

if (!Loader::includeModule('corsik.yadelivery'))
{
	return 'Module corsik.yadelivery not found';
}

$coreAjax = new Core();
$data = $coreAjax->init($post);

print Json::encode($data);
