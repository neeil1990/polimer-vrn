<?php

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Yandex\Market;

define('BX_SESSION_ID_CHANGE', false);
define('NOT_CHECK_PERMISSIONS', true); // allow from crm

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php';

Loc::loadMessages(__FILE__);

$request = Main\Context::getCurrent()->getRequest();
$assets = Main\Page\Asset::getInstance();
$requestView = $request->get('view');

if ($requestView === 'dialog')
{
	$assets = $assets->setAjax();
	$APPLICATION->oAsset = $assets;
}
else
{
	require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_after.php';
}

if (!Main\Loader::includeModule('yandex.market'))
{
	\CAdminMessage::ShowMessage([
		'TYPE' => 'ERROR',
		'MESSAGE' => Loc::getMessage('YANDEX_MARKET_TRADING_PACK_EDIT_REQUIRE_MODULE')
	]);
}
else if (!Market\Ui\Access::isProcessTradingAllowed())
{
	\CAdminMessage::ShowMessage([
		'TYPE' => 'ERROR',
		'MESSAGE' => Loc::getMessage('YANDEX_MARKET_TRADING_PACK_EDIT_ACCESS_DENIED')
	]);
}
else
{
	$request = Main\Context::getCurrent()->getRequest();
	$primary = $request->get('id');
	$contextMenu = [];

	if ((int)$primary > 0)
	{
		$postActionDelete = [
			'id' => $primary,
			'postAction' => 'delete',
			'sessid' => bitrix_sessid(),
			'lang' => LANGUAGE_ID,
		];

		$contextMenu[] = [
			'ICON' => 'btn_delete',
			'LINK' => $APPLICATION->GetCurPageParam(http_build_query($postActionDelete), array_merge(
				array_keys($postActionDelete),
				[ 'view', 'bxsender' ]
			)),
			'TEXT' => Market\Config::getLang('TRADING_PACK_EDIT_ACTION_DELETE'),
		];
	}

	$APPLICATION->IncludeComponent('yandex.market:admin.form.edit', '', [
		'TITLE' => Market\Config::getLang('TRADING_PACK_EDIT_TITLE_EDIT'),
		'TITLE_ADD' => Market\Config::getLang('TRADING_PACK_EDIT_TITLE_ADD'),
		'FORM_ID'   => 'YANDEX_MARKET_ADMIN_TRADING_PACK_EDIT',
		'ALLOW_SAVE' => Market\Ui\Access::isProcessTradingAllowed(),
		'PRIMARY' => $primary,
		'PROVIDER_TYPE' => 'Data',
		'DATA_CLASS_NAME' => Market\Ui\Trading\Internals\PackTable::class,
		'LAYOUT' => $requestView === 'dialog' ? 'raw' : null,
		'CONTEXT_MENU' => $contextMenu,
		'TABS' => [
			[
				'name' => Market\Config::getLang('TRADING_PACK_EDIT_TAB_COMMON'),
				'fields' => [
					'NAME',
					'WIDTH',
					'HEIGHT',
					'DEPTH',
					'WEIGHT',
				]
			],
		]
	]);
}

if ($requestView === 'dialog')
{
	echo $assets->getCss();
	echo $assets->getJs();
}
else
{
	require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin_before.php';
}

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin_after.php';
