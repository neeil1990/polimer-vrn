<?php

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Yandex\Market;

define('BX_SESSION_ID_CHANGE', false);

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin.php';

Loc::loadMessages(__FILE__);

if (!Main\Loader::includeModule('yandex.market'))
{
	\CAdminMessage::ShowMessage([
		'TYPE' => 'ERROR',
		'MESSAGE' => Loc::getMessage('YANDEX_MARKET_GROUP_EDIT_REQUIRE_MODULE')
	]);
}
else if (!Market\Ui\Access::isProcessExportAllowed())
{
	\CAdminMessage::ShowMessage([
		'TYPE' => 'ERROR',
		'MESSAGE' => Loc::getMessage('YANDEX_MARKET_GROUP_EDIT_ACCESS_DENIED')
	]);
}
else
{
	$request = Main\Context::getCurrent()->getRequest();
	$service = trim($request->get('service'));
	$parent = (int)$request->get('parent');
	$baseQuery = [
		'lang' => LANGUAGE_ID,
	];

	if ($service !== '')
	{
		$baseQuery['service'] = $service;
	}

	$APPLICATION->IncludeComponent('yandex.market:admin.form.edit', '', [
		'TITLE' => Market\Config::getLang('GROUP_EDIT_TITLE_EDIT'),
		'TITLE_ADD' => Market\Config::getLang('GROUP_EDIT_TITLE_ADD'),
		'FORM_ID'   => 'YANDEX_MARKET_ADMIN_GROUP_EDIT',
		'ALLOW_SAVE' => Market\Ui\Access::isWriteAllowed(),
		'PRIMARY'   => !empty($_GET['id']) ? $_GET['id'] : null,
		'LIST_URL'  => Market\Ui\Admin\Path::getModuleUrl('setup_list', $baseQuery),
		'PROVIDER_TYPE' => 'Group',
		'DATA_CLASS_NAME' => Market\Export\Setup\Internals\GroupTable::getClassName(),
		'SERVICE' => $service,
		'PARENT_GROUP' => $parent,
		'CONTEXT_MENU' => [
			[
				'ICON' => 'btn_list',
				'LINK' => Market\Ui\Admin\Path::getModuleUrl('setup_list', $baseQuery),
				'TEXT' => Market\Config::getLang('GROUP_EDIT_CONTEXT_MENU_LIST')
			]
		],
		'TABS' => [
			[
				'name' => Market\Config::getLang('GROUP_EDIT_TAB_COMMON'),
				'fields' => [
					'PARENT_ID',
					'NAME',
					'UI_SERVICE',
				]
			],
		]
	]);
}

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php';
