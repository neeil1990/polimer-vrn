<?php

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Yandex\Market;

// prolog

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php';

$request = Main\Context::getCurrent()->getRequest();
$requestView = $request->getQuery('view');
$assets = Main\Page\Asset::getInstance();

if ($requestView === 'popup')
{
	require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_popup_admin.php';
}
else if ($requestView === 'tab')
{
	$assets = $assets->setAjax();
	$APPLICATION->oAsset = $assets;
}
else
{
	require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_after.php';
}

// body

Loc::loadMessages(__FILE__);

$APPLICATION->SetTitle(Loc::getMessage('YANDEX_MARKET_TRADING_ORDER_VIEW_TITLE'));

$controller = null;
$state = null;

try
{
	if (!Main\Loader::includeModule('yandex.market'))
	{
		$message = Loc::getMessage('YANDEX_MARKET_TRADING_ORDER_VIEW_REQUIRE_MODULE');
		throw new Main\SystemException($message);
	}

	if (!Market\Ui\Access::isProcessTradingAllowed())
	{
		$message = Loc::getMessage('YANDEX_MARKET_TRADING_ORDER_VIEW_ACCESS_DENIED');
		throw new Main\SystemException($message);
	}

	$request = Main\Context::getCurrent()->getRequest();

	$APPLICATION->IncludeComponent('yandex.market:trading.order.view', '', [
		'EXTERNAL_ID' => $request->get('id'),
		'SETUP_ID' => $request->get('setup'),
		'SITE_ID' => $request->get('site'),
		'CHECK_ACCESS' => !Market\Ui\Access::isWriteAllowed(),
	], false, [ 'HIDE_ICONS' => 'Y' ]);
}
catch (Main\SystemException $exception)
{
	\CAdminMessage::ShowMessage([
		'TYPE' => 'ERROR',
		'MESSAGE' => $exception->getMessage(),
	]);
}

// epilog

if ($requestView === 'popup')
{
	require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_popup_admin.php';
}
else if ($requestView === 'tab')
{
	echo $assets->getCss();
	echo $assets->getJs();
}
else
{
	require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin_before.php';
}

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin_after.php';