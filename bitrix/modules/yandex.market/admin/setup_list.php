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
		'MESSAGE' => Loc::getMessage('YANDEX_MARKET_ADMIN_SETUP_LIST_REQUIRE_MODULE')
	]);
}
else if (!Market\Ui\Access::isProcessExportAllowed())
{
	\CAdminMessage::ShowMessage([
		'TYPE' => 'ERROR',
		'MESSAGE' => Loc::getMessage('YANDEX_MARKET_ADMIN_SETUP_LIST_ACCESS_DENIED')
	]);
}
else
{
	Market\Metrika::load();

	$request = Main\Context::getCurrent()->getRequest();
	$service = trim($request->get('service'));
	$gridId = 'YANDEX_MARKET_ADMIN_SETUP_LIST';
	$baseQuery = [
		'lang' => LANGUAGE_ID,
	];

	if ($service !== '' && Market\Ui\Service\Manager::isExists($service))
	{
		$gridId .= '_' . Market\Data\TextString::toUpper($service);
		$baseQuery['service'] = $service;
	}

	$APPLICATION->IncludeComponent(
		'yandex.market:admin.grid.list',
		'',
		[
			'GRID_ID' => $gridId,
			'ALLOW_SAVE' => Market\Ui\Access::isWriteAllowed(),
			'PROVIDER_TYPE' => 'Setup',
			'MODEL_CLASS_NAME' => Market\Export\Setup\Model::class,
			'SERVICE' => $service,
			'GROUP_EDIT_URL' => Market\Ui\Admin\Path::getModuleUrl('setup_group_edit', $baseQuery),
			'EDIT_URL' => Market\Ui\Admin\Path::getModuleUrl('setup_edit', $baseQuery) . '&id=#ID#',
			'ADD_URL' => Market\Ui\Admin\Path::getModuleUrl('setup_edit', $baseQuery),
			'TITLE' => Loc::getMessage('YANDEX_MARKET_ADMIN_SETUP_LIST_PAGE_TITLE'),
			'NAV_TITLE' => Loc::getMessage('YANDEX_MARKET_ADMIN_SETUP_LIST_NAV_TITLE'),
			'LIST_FIELDS' => [
				'ID',
				'NAME',
				'EXPORT_SERVICE',
				'EXPORT_FORMAT',
				'DOMAIN',
				'HTTPS',
				'IBLOCK',
				'FILE_NAME',
				'ENABLE_AUTO_DISCOUNTS',
				'ENABLE_CPA',
				'AUTOUPDATE',
				'REFRESH_PERIOD',
				'EXPORT_DATE',
				'GROUP',
			],
			'DEFAULT_LIST_FIELDS' => [
				'ID',
				'NAME',
				'EXPORT_SERVICE',
				'EXPORT_FORMAT',
				'DOMAIN',
				'HTTPS',
				'IBLOCK',
				'FILE_NAME',
			],
			'DEFAULT_FILTER_FIELDS' => [
				'ID',
				'GROUP',
				'NAME',
			],
			'CONTEXT_MENU' => [],
			'ROW_ACTIONS' => [
				// item actions

				'RUN' => [
					'URL' => Market\Ui\Admin\Path::getModuleUrl('setup_run', $baseQuery) . '&id=#ID#',
					'ICON' => 'unpack',
					'TEXT' => Loc::getMessage('YANDEX_MARKET_ADMIN_SETUP_LIST_ROW_ACTION_RUN')
				],
				'EDIT' => [
					'URL' => Market\Ui\Admin\Path::getModuleUrl('setup_edit', $baseQuery) . '&id=#ID#',
					'ICON' => 'edit',
					'TEXT' => Loc::getMessage('YANDEX_MARKET_ADMIN_SETUP_LIST_ROW_ACTION_EDIT'),
					'DEFAULT' => true
				],
				'LOG' => [
					'URL' =>
						Market\Ui\Admin\Path::getModuleUrl('log', $baseQuery)
						. '&set_filter=Y&apply_filter=Y&find_setup=#ID#',
					'ICON' => 'view',
					'TEXT' => Loc::getMessage('YANDEX_MARKET_ADMIN_SETUP_LIST_ROW_ACTION_LOG'),
				],
				'COPY' => [
					'URL' => Market\Ui\Admin\Path::getModuleUrl('setup_edit', $baseQuery) . '&id=#ID#&copy=Y',
					'ICON' => 'copy',
					'TEXT' => Loc::getMessage('YANDEX_MARKET_ADMIN_SETUP_LIST_ROW_ACTION_COPY')
				],
				'DELETE' => [
					'ICON' => 'delete',
					'TEXT' => Loc::getMessage('YANDEX_MARKET_ADMIN_SETUP_LIST_ROW_ACTION_DELETE'),
					'CONFIRM' => 'Y',
					'CONFIRM_MESSAGE' => Loc::getMessage('YANDEX_MARKET_ADMIN_SETUP_LIST_ROW_ACTION_DELETE_CONFIRM')
				],

				// group actions

				'EDIT_GROUP' => [
					'URL' => Market\Ui\Admin\Path::getModuleUrl('setup_group_edit', $baseQuery) . '&id=#PRIMARY#',
					'ICON' => 'edit',
					'TEXT' => Loc::getMessage('YANDEX_MARKET_ADMIN_SETUP_LIST_ROW_ACTION_GROUP_EDIT'),
					'ROW_TYPE' => [ 'GROUP' ],
				],

				'OPEN_GROUP' => [
					'QUERY' => [
						'find_group' => '#PRIMARY#',
						'set_filter' => 'Y',
						'apply_filter' => 'Y',
					],
					'TEXT' => Loc::getMessage('YANDEX_MARKET_ADMIN_SETUP_LIST_ROW_ACTION_GROUP_OPEN'),
					'DEFAULT' => true,
					'ROW_TYPE' => [ 'GROUP' ],
				],

				'DELETE_GROUP' => [
					'ICON' => 'delete',
					'TEXT' => Loc::getMessage('YANDEX_MARKET_ADMIN_SETUP_LIST_ROW_ACTION_GROUP_DELETE'),
					'CONFIRM' => 'Y',
					'CONFIRM_MESSAGE' => Loc::getMessage('YANDEX_MARKET_ADMIN_SETUP_LIST_ROW_ACTION_GROUP_DELETE_CONFIRM'),
					'ROW_TYPE' => [ 'GROUP' ],
				],
			],
			'GROUP_ACTIONS' => [
				'delete' => Loc::getMessage('YANDEX_MARKET_ADMIN_SETUP_LIST_ROW_ACTION_DELETE')
			]
		]
	);

	$note = (string)Loc::getMessage('YANDEX_MARKET_ADMIN_SETUP_LIST_NOTE_' . Market\Data\TextString::toUpper($service));

	if ($note !== '')
	{
		echo BeginNote('style="max-width: 600px;"');
		echo $note;
		echo EndNote();
	}

	Market\Ui\Checker\Announcement::show();
}

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php';