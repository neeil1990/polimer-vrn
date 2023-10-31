<?php

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Yandex\Market;

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin.php';

Loc::loadMessages(__FILE__);

if (!Main\Loader::includeModule('yandex.market'))
{
	\CAdminMessage::ShowMessage([
		'TYPE' => 'ERROR',
		'MESSAGE' => Loc::getMessage('YANDEX_MARKET_ADMIN_TRADING_LIST_REQUIRE_MODULE')
	]);
}
else if (!Market\Ui\Access::isReadAllowed())
{
	\CAdminMessage::ShowMessage([
		'TYPE' => 'ERROR',
		'MESSAGE' => Market\Config::getLang('ADMIN_TRADING_LIST_ACCESS_DENIED')
	]);
}
else
{
	Market\Metrika::load();

	$request = Main\Context::getCurrent()->getRequest();
	$service = trim($request->get('service'));
	$gridId = 'YANDEX_MARKET_ADMIN_TRADING_LIST';
	$baseQuery = [
		'lang' => LANGUAGE_ID,
	];
	$postActionReinstall = [
		'postAction' => 'reinstall',
	];

	if ($service !== '' && Market\Ui\Service\Manager::isExists($service))
	{
		$gridId .= '_' . Market\Data\TextString::toUpper($service);
		$baseQuery['service'] = $service;
	}

	$APPLICATION->IncludeComponent('yandex.market:admin.grid.list', '', [
		'GRID_ID' => $gridId,
		'ALLOW_SAVE' => Market\Ui\Access::isWriteAllowed(),
		'PROVIDER_TYPE' => 'TradingSetup',
		'MODEL_CLASS_NAME' => Market\Trading\Setup\Model::class,
		'SERVICE' => $service,
		'EDIT_URL' => Market\Ui\Admin\Path::getModuleUrl('trading_edit', $baseQuery) . '&id=#ID#',
		'ADD_URL' => Market\Ui\Admin\Path::getModuleUrl('trading_edit', $baseQuery),
		'TITLE' => Market\Config::getLang('ADMIN_TRADING_LIST_PAGE_TITLE'),
		'NAV_TITLE' => Market\Config::getLang('ADMIN_TRADING_LIST_NAV_TITLE'),
		'LIST_FIELDS' => [
			'ID',
			'NAME',
			'TRADING_SERVICE',
			'TRADING_BEHAVIOR',
			'CAMPAIGN_ID',
			'BUSINESS_ID',
			'SITE_ID',
			'ACTIVE',
			'YANDEX_INCOMING_URL',
		],
		'DEFAULT_LIST_FIELDS' => [
			'TRADING_BEHAVIOR',
			'CAMPAIGN_ID',
			'BUSINESS_ID',
			'SITE_ID',
			'ACTIVE',
			'YANDEX_INCOMING_URL',
		],
		'CONTEXT_MENU' => [
			[
				'TEXT' => Market\Config::getLang('ADMIN_TRADING_LIST_BUTTON_ADD'),
				'LINK' => Market\Ui\Admin\Path::getModuleUrl('trading_setup', $baseQuery),
				'ICON' => 'btn_new',
			],
			[
				'TEXT' => Market\Config::getLang('ADMIN_TRADING_LIST_BUTTON_IMPORT'),
				'LINK' => Market\Ui\Admin\Path::getModuleUrl('trading_import', $baseQuery),
			],
			[
				'TEXT' => Market\Config::getLang('ADMIN_TRADING_LIST_BUTTON_REINSTALL'),
				'LINK' => $APPLICATION->GetCurPageParam(
					http_build_query([ 'postAction' => 'reinstall' ]),
					[ 'postAction' ]
				),
			],
			[
				'TEXT' => Market\Config::getLang('ADMIN_TRADING_LIST_BUTTON_ENABLE_PUSH_STOCKS'),
				'LINK' => $APPLICATION->GetCurPageParam(
					http_build_query([ 'postAction' => 'enablePushStocks' ]),
					[ 'postAction' ]
				),
			],
			[
				'TEXT' => Market\Config::getLang('ADMIN_TRADING_LIST_BUTTON_DISABLE_PUSH_STOCKS'),
				'LINK' => $APPLICATION->GetCurPageParam(
					http_build_query([ 'postAction' => 'disablePushStocks' ]),
					[ 'postAction' ]
				),
			],
		],
		'ROW_ACTIONS' => [
			'SETUP' => [
				'URL' => Market\Ui\Admin\Path::getModuleUrl('trading_setup', $baseQuery) . '&id=#ID#',
				'ICON' => 'setting',
				'TEXT' => Market\Config::getLang('ADMIN_TRADING_LIST_ROW_ACTION_SETUP'),
			],
			'EDIT' => [
				'URL' => Market\Ui\Admin\Path::getModuleUrl('trading_edit', array_diff_key($baseQuery, [ 'service' => true ])) . '&service=#TRADING_SERVICE#&id=#ID#',
				'ICON' => 'edit',
				'TEXT' => Market\Config::getLang('ADMIN_TRADING_LIST_ROW_ACTION_EDIT'),
				'DEFAULT' => true
			],
			'LOG' => [
				'URL' => Market\Ui\Admin\Path::getModuleUrl('trading_log', $baseQuery + [
					'set_filter' => 'Y',
					'apply_filter' => 'Y',
				]) . '&find_setup=#ID#',
				'ICON' => 'view',
				'TEXT' => Market\Config::getLang('ADMIN_TRADING_LIST_ROW_ACTION_LOG'),
				'DEFAULT' => true
			],
			'COPY' => [
				'URL' => Market\Ui\Admin\Path::getModuleUrl('trading_setup', $baseQuery) . '&id=#ID#&copy=Y',
				'ICON' => 'copy',
				'TEXT' => Market\Config::getLang('ADMIN_TRADING_LIST_ROW_ACTION_COPY')
			],
			'ACTIVATE' => [
				'ACTION' => 'activate',
				'TEXT' => Market\Config::getLang('ADMIN_TRADING_LIST_ROW_ACTION_ACTIVATE')
			],
			'DEACTIVATE' => [
				'ACTION' => 'deactivate',
				'TEXT' => Market\Config::getLang('ADMIN_TRADING_LIST_ROW_ACTION_DEACTIVATE'),
				'CONFIRM' => 'Y',
				'CONFIRM_MESSAGE' => Market\Config::getLang('ADMIN_TRADING_LIST_ROW_ACTION_DEACTIVATE_CONFIRM')
			],
			'DELETE' => [
				'ICON' => 'delete',
				'TEXT' => Market\Config::getLang('ADMIN_TRADING_LIST_ROW_ACTION_DELETE'),
				'CONFIRM' => 'Y',
				'CONFIRM_MESSAGE' => Market\Config::getLang('ADMIN_TRADING_LIST_ROW_ACTION_DELETE_CONFIRM')
			],
		],
		'GROUP_ACTIONS' => [
			'activate' => Market\Config::getLang('ADMIN_TRADING_LIST_ROW_ACTION_ACTIVATE'),
			'deactivate' => Market\Config::getLang('ADMIN_TRADING_LIST_ROW_ACTION_DEACTIVATE'),
		]
	]);

	Market\Ui\Checker\Announcement::show();
}

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php';