<?php 
/** @noinspection DuplicatedCode */
use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Yandex\Market;

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin.php';

/** @var CMain $APPLICATION */

Loc::loadMessages(__FILE__);

if (!Main\Loader::includeModule('yandex.market'))
{
	\CAdminMessage::ShowMessage([
		'TYPE' => 'ERROR',
		'MESSAGE' => Loc::getMessage('YANDEX_MARKET_ADMIN_COLLECTION_LIST_REQUIRE_MODULE')
	]);
}
else if (!Market\Ui\Access::isProcessExportAllowed())
{
	\CAdminMessage::ShowMessage([
		'TYPE' => 'ERROR',
		'MESSAGE' => Loc::getMessage('YANDEX_MARKET_ADMIN_COLLECTION_LIST_ACCESS_DENIED')
	]);
}
else
{
	$APPLICATION->IncludeComponent('yandex.market:admin.grid.list', '', [
		'GRID_ID' => 'YANDEX_MARKET_ADMIN_COLLECTION_RESULT',
		'PROVIDER_TYPE' => 'Data',
		'DATA_CLASS_NAME' => Market\Export\Run\Storage\CollectionTable::class,
		'USE_FILTER' => 'Y',
		'TITLE' => Market\Config::getLang('ADMIN_COLLECTION_RESULT_TITLE'),
		'NAV_TITLE' => Market\Config::getLang('ADMIN_COLLECTION_RESULT_NAV_TITLE'),
		'LIST_FIELDS' => [
			'SETUP',
			'COLLECTION_ID',
			'PRIMARY',
			'STATUS',
			'LOG',
			'TIMESTAMP_X'
		],
		'FILTER_FIELDS' => [
			'SETUP',
			'COLLECTION_ID',
			'PRIMARY',
			'STATUS',
			'TIMESTAMP_X',
		],
		'PRIMARY' => [
			'SETUP_ID',
			'ELEMENT_ID',
		],
		'ROW_ACTIONS' => [
			'LOG' => [
				'URL' =>
					Market\Ui\Admin\Path::getModuleUrl('log', [
						'set_filter' => 'Y',
						'apply_filter' => 'Y',
						'popup' => 'Y',
					])
					. '&find_collection_id=#COLLECTION_ID#&find_setup=#SETUP_ID#',
				'TEXT' => Market\Config::getLang('ADMIN_COLLECTION_RESULT_ROW_ACTION_LOG'),
				'WINDOW' => 'Y'
			],
			'XML_CONTENT' => [
				'URL' =>
					Market\Ui\Admin\Path::getModuleUrl('xml_element', [
						'type' => 'collection',
						'popup' => 'Y',
					])
					. '&id=#ELEMENT_ID#&setup=#SETUP_ID#',
				'TEXT' => Market\Config::getLang('ADMIN_COLLECTION_RESULT_ROW_XML_CONTENT'),
				'WINDOW' => 'Y'
			]
		]
	]);
}

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php';