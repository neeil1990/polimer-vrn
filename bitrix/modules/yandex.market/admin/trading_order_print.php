<?php

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Yandex\Market;

define('NOT_CHECK_PERMISSIONS', true); // allow from crm

// prolog

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php';

$request = Main\Context::getCurrent()->getRequest();
$requestView = $request->get('view');
$assets = Main\Page\Asset::getInstance();

if ($requestView === 'whiteboard')
{
	die(); // blank page
}
else if ($requestView === 'print')
{
	echo '<!DOCTYPE html>';
	echo '<html><head>';
	echo '<title>';
	$APPLICATION->ShowTitle();
	echo '</title>';
	$APPLICATION->AddBufferContent([&$APPLICATION, 'GetCSS'], true, true, Main\Page\AssetShowTargetType::BODY);
	$APPLICATION->AddBufferContent([&$APPLICATION, 'GetHeadStrings'], 'PRINT');
	$APPLICATION->AddBufferContent([&$APPLICATION, 'GetHeadScripts'], Main\Page\AssetShowTargetType::TEMPLATE_PAGE);
	echo '</head><body>';
}
else if ($requestView === 'popup')
{
	require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_popup_admin.php';
}
else if ($requestView === 'tab' || $requestView === 'dialog')
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

$APPLICATION->SetTitle(Loc::getMessage('YANDEX_MARKET_TRADING_ORDER_PRINT_TITLE'));

$controller = null;
$state = null;
$isAllowPrint = false;

try
{
	if (!Main\Loader::includeModule('yandex.market'))
	{
		$message = Loc::getMessage('YANDEX_MARKET_TRADING_ORDER_PRINT_REQUIRE_MODULE');
		throw new Main\SystemException($message);
	}

	if (!Market\Ui\Access::isProcessTradingAllowed())
	{
		$message = Loc::getMessage('YANDEX_MARKET_TRADING_ORDER_PRINT_ACCESS_DENIED');
		throw new Main\SystemException($message);
	}

	$request = Main\Context::getCurrent()->getRequest();

	$APPLICATION->IncludeComponent('yandex.market:trading.print.form', '', [
		'EXTERNAL_ID' => $request->get('id'),
		'SETUP_ID' => $request->get('setup'),
		'TYPE' => $request->get('type'),
		'USE_ADDITIONAL' => ($request->get('alone') !== 'Y'),
		'CHECK_ACCESS' => !Market\Ui\Access::isWriteAllowed(),
	], false, [ 'HIDE_ICONS' => 'Y' ]);

	$isAllowPrint = ($APPLICATION->GetPageProperty('YAMARKET_PAGE_PRINT', 'Y') !== 'N');
}
catch (Main\SystemException $exception)
{
	\CAdminMessage::ShowMessage([
		'TYPE' => 'ERROR',
		'MESSAGE' => $exception->getMessage(),
	]);
}

// epilog

if ($requestView === 'print')
{
	if ($isAllowPrint)
	{
		echo '<script> window.addEventListener("load", function() { 
			if (window.requestAnimationFrame) {
				window.requestAnimationFrame(function() {
					window.print();
				});
			} else {
				window.print();
			}
		}); </script>';
	}
	echo '</body></html>';
}
else if ($requestView === 'popup')
{
	require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_popup_admin.php';
}
else if ($requestView === 'tab' || $requestView === 'dialog')
{
	echo $assets->getCss();
	echo $assets->getJs();
}
else
{
	require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin_before.php';
}

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin_after.php';