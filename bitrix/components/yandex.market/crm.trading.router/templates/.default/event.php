<?php

use Bitrix\Main\Localization\Loc;
use Yandex\Market;

Market\Ui\Assets::loadPlugin('admin', 'css');
Market\Ui\Assets::loadPlugin('grain', 'css');

$APPLICATION->IncludeComponent('yandex.market:admin.grid.list', '', [
	'GRID_ID' => 'YANDEX_MARKET_ADMIN_TRADING_LOG',
	'PROVIDER_TYPE' => 'Log',
	'DATA_CLASS_NAME' => Market\Logger\Trading\Table::class,
	'SERVICE' => $arParams['SERVICE_CODE'],
	'SERVICE_BEHAVIOR' => Market\Ui\Service\Manager::BEHAVIOR_TRADING,
	'USE_SERVICE' => 'Y',
	'TITLE' => Loc::getMessage('YANDEX_MARKET_CRM_ROUTER_EVENT_TITLE'),
	'LIST_FIELDS' => [
		'TIMESTAMP_X',
		'AUDIT',
		'LEVEL',
		'MESSAGE',
		'ORDER_ID',
		'SETUP',
		'URL',
		'CONTEXT',
		'TRACE',
		'DEBUG',
	],
	'DEFAULT_LIST_FIELDS' => [
		'TIMESTAMP_X',
		'AUDIT',
		'LEVEL',
		'MESSAGE',
		'ORDER_ID',
		'SETUP',
		'DEBUG',
	],
	'DEFAULT_SORT' => [ 'ID' => 'DESC' ],
	'CONTEXT_MENU_EXCEL' => 'Y',
	'ROW_ACTIONS' => [
		'DELETE' => [
			'ICON' => 'delete',
			'TEXT' => Loc::getMessage('YANDEX_MARKET_CRM_ROUTER_EVENT_ACTION_DELETE'),
			'CONFIRM' => 'Y',
			'CONFIRM_MESSAGE' => Loc::getMessage('YANDEX_MARKET_CRM_ROUTER_EVENT_ACTION_DELETE_CONFIRM')
		]
	],
	'GROUP_ACTIONS' => [
		'delete' => Loc::getMessage('YANDEX_MARKET_CRM_ROUTER_EVENT_ACTION_DELETE'),
	],
	'ALLOW_BATCH' => 'Y',
]);