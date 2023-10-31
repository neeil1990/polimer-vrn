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
		'MESSAGE' => Loc::getMessage('YANDEX_MARKET_ADMIN_CONFIRMATION_EDIT_REQUIRE_MODULE')
	]);
}
else if (!Market\Ui\Access::isReadAllowed())
{
	\CAdminMessage::ShowMessage([
		'TYPE' => 'ERROR',
		'MESSAGE' => Loc::getMessage('YANDEX_MARKET_ADMIN_CONFIRMATION_EDIT_ACCESS_DENIED')
	]);
}
else
{
	$request = Main\Context::getCurrent()->getRequest();

	$APPLICATION->IncludeComponent('yandex.market:admin.form.edit', '', [
		'TITLE' => Market\Config::getLang('ADMIN_CONFIRMATION_EDIT_TITLE_EDIT'),
		'TITLE_ADD' => Market\Config::getLang('ADMIN_CONFIRMATION_EDIT_TITLE_ADD'),
		'FORM_ID' => 'YANDEX_MARKET_ADMIN_ADMIN_CONFIRMATION_EDIT',
		'ALLOW_SAVE' => Market\Ui\Access::isWriteAllowed(),
		'PRIMARY' => $request->getQuery('id'),
		'COPY' => $request->getQuery('copy') === 'Y',
		'LIST_URL'  => Market\Ui\Admin\Path::getModuleUrl('confirmation_list', [ 'lang' => LANGUAGE_ID ]),
		'PROVIDER_TYPE' => 'Data',
		'DATA_CLASS_NAME' => Market\Confirmation\Setup\Table::class,
		'USE_METRIKA' => 'Y',
		'CONTEXT_MENU' => [
			[
				'ICON' => 'btn_list',
				'LINK' => Market\Ui\Admin\Path::getModuleUrl('confirmation_list', [ 'lang' => LANGUAGE_ID ]),
				'TEXT' => Market\Config::getLang('ADMIN_CONFIRMATION_EDIT_CONTEXT_MENU_LIST')
			]
		],
		'TABS' => [
			[
				'name' => Market\Config::getLang('ADMIN_CONFIRMATION_EDIT_TAB_COMMON'),
				'fields' => [
					'DOMAIN',
					'BEHAVIOR',
					'CONTENTS',
				]
			],
		],
	]);
}

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php';
