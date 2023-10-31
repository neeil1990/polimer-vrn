<?php

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Yandex\Market;

/** @var CMain $APPLICATION */

/** @noinspection PhpIncludeInspection */
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin.php';

Loc::loadMessages(__FILE__);

if (!Main\Loader::includeModule('yandex.market'))
{
	CAdminMessage::ShowMessage([
        'TYPE' => 'ERROR',
        'MESSAGE' => Loc::getMessage('YANDEX_MARKET_COLLECTION_EDIT_REQUIRE_MODULE')
    ]);
}
else if (!Market\Ui\Access::isProcessExportAllowed())
{
	CAdminMessage::ShowMessage([
		'TYPE' => 'ERROR',
		'MESSAGE' => Loc::getMessage('YANDEX_MARKET_COLLECTION_EDIT_ACCESS_DENIED')
	]);
}
else
{
	$id = null;
	$hasId = false;
	$contextMenu = [
        [
            'ICON' => 'btn_list',
            'LINK' => '/bitrix/admin/yamarket_collection_list.php?lang=' . LANGUAGE_ID,
            'TEXT' => Market\Config::getLang('COLLECTION_EDIT_CONTEXT_MENU_LIST')
        ]
    ];

	if (!empty($_GET['id']))
    {
        $id = (int)$_GET['id'];
        $hasId = true;

        $contextMenu[] = [
            'LINK' => '/bitrix/admin/yamarket_collection_result.php?lang=' . LANGUAGE_ID . '&find_collection_id=' . $id . '&set_filter=Y&apply_filter=Y',
            'TEXT' => Market\Config::getLang('COLLECTION_EDIT_CONTEXT_MENU_EXPORT_RESULT')
        ];

        $contextMenu[] = [
            'LINK' => '/bitrix/admin/yamarket_log.php?lang=' . LANGUAGE_ID . '&find_collection_id=' . $id . '&set_filter=Y&apply_filter=Y',
            'TEXT' => Market\Config::getLang('COLLECTION_EDIT_CONTEXT_MENU_LOG')
        ];
    }

	$APPLICATION->IncludeComponent('yandex.market:admin.form.edit', '', [
		'TITLE' => Market\Config::getLang('COLLECTION_EDIT_TITLE_EDIT'),
		'TITLE_ADD' => Market\Config::getLang('COLLECTION_EDIT_TITLE_ADD'),
		'BTN_SAVE' => Market\Config::getLang('COLLECTION_EDIT_BTN_SAVE'),
		'FORM_ID'   => 'YANDEX_MARKET_ADMIN_COLLECTION_EDIT',
		'FORM_BEHAVIOR' => 'steps',
		'ALLOW_SAVE' => Market\Ui\Access::isWriteAllowed(),
		'PRIMARY' => $id,
		'COPY' => isset($_GET['copy']) && $_GET['copy'] === 'Y',
		'LIST_URL' => '/bitrix/admin/yamarket_collection_list.php?lang=' . LANGUAGE_ID,
        'SAVE_URL' => '/bitrix/admin/yamarket_collection_run.php?lang=' . LANGUAGE_ID . '&id=#ID#',
		'PROVIDER_TYPE' => 'Collection',
		'MODEL_CLASS_NAME' => Market\Export\Collection\Model::getClassName(),
		'USE_METRIKA' => 'Y',
		'CONTEXT_MENU' => $contextMenu,
		'TABS' => [
			[
				'name' => Market\Config::getLang('COLLECTION_EDIT_TAB_COMMON'),
				'fields' => [
					'ACTIVE',
					'NAME',
					'STRATEGY',
					'START_DATE',
					'FINISH_DATE',
					'SETUP_EXPORT_ALL',
					'SETUP',
				],
			],
			[
				'name' => Market\Config::getLang('COLLECTION_EDIT_TAB_RULE'),
				'layout' => 'product-filter',
				'fields' => [
					'STRATEGY_SETTINGS',
					'LIMIT_SETTINGS',
					'COLLECTION_PRODUCT',
				],
			],
		],
		'PRODUCT_FILTER_FIELDS' => [
			'COLLECTION_PRODUCT',
		],
	]);
}

/** @noinspection PhpIncludeInspection */
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php';
