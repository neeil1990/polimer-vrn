<?php

define('STOP_STATISTICS', true);
define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC', 'Y');
define('DisableEventsCheck', true);
define('BX_SECURITY_SHOW_MESSAGE', true);
define('NOT_CHECK_PERMISSIONS', true);

use Bitrix\Main\Application;
use Bitrix\Main\Loader;
use Bitrix\Main\Web\Json;
use Bitrix\Main\Web\PostDecodeFilter;
use Corsik\YaDelivery\Table\RulesTable;

require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_before.php");

$request = Application::getInstance()->getContext()->getRequest();
$request->addFilter(new PostDecodeFilter);
$postList = $request->getPostList()->toArray();

if (!Loader::includeModule('corsik.yadelivery'))
{
	return 'Module corsik.yadelivery not found';
}

$postValues = RulesTable::getMapMatchArray($postList);
$postValues['RULE'] = JSON::encode($postValues['RULE']);

if (isset($postList['ID']) && (int)$postList['ID'] === 0)
{
	$result = RulesTable::add($postValues);
}
else
{
	$result = RulesTable::update($postValues['ID'], $postValues);
}
