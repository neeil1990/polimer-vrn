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
        'MESSAGE' => Loc::getMessage('YANDEX_MARKET_PROMO_EDIT_REQUIRE_MODULE')
    ]);
}
else if (!Market\Ui\Access::isProcessExportAllowed())
{
	\CAdminMessage::ShowMessage([
		'TYPE' => 'ERROR',
		'MESSAGE' => Loc::getMessage('YANDEX_MARKET_PROMO_EDIT_ACCESS_DENIED')
	]);
}
else
{
	$id = null;
	$hasId = false;
	$contextMenu = [
        [
            'ICON' => 'btn_list',
            'LINK' => '/bitrix/admin/yamarket_promo_list.php?lang=' . LANGUAGE_ID,
            'TEXT' => Market\Config::getLang('PROMO_EDIT_CONTEXT_MENU_LIST')
        ]
    ];

	if (!empty($_GET['id']))
    {
        $id = (int)$_GET['id'];
        $hasId = true;

        $contextMenu[] = [
            'LINK' => '/bitrix/admin/yamarket_promo_result.php?lang=' . LANGUAGE_ID . '&find_element_id=' . $id . '&set_filter=Y&apply_filter=Y',
            'TEXT' => Market\Config::getLang('PROMO_EDIT_CONTEXT_MENU_EXPORT_RESULT')
        ];

        $contextMenu[] = [
            'LINK' => '/bitrix/admin/yamarket_log.php?lang=' . LANGUAGE_ID . '&find_promo_id=' . $id . '&set_filter=Y&apply_filter=Y',
            'TEXT' => Market\Config::getLang('PROMO_EDIT_CONTEXT_MENU_LOG')
        ];
    }

	$APPLICATION->IncludeComponent('yandex.market:admin.form.edit', '', [
		'TITLE' => Market\Config::getLang('PROMO_EDIT_TITLE_EDIT'),
		'TITLE_ADD' => Market\Config::getLang('PROMO_EDIT_TITLE_ADD'),
		'BTN_SAVE' => Market\Config::getLang('PROMO_EDIT_BTN_SAVE'),
		'FORM_ID'   => 'YANDEX_MARKET_ADMIN_PROMO_EDIT',
		'FORM_BEHAVIOR' => 'steps',
		'ALLOW_SAVE' => Market\Ui\Access::isWriteAllowed(),
		'PRIMARY' => $id,
		'COPY' => isset($_GET['copy']) ? $_GET['copy'] === 'Y' : false,
		'LIST_URL' => '/bitrix/admin/yamarket_promo_list.php?lang=' . LANGUAGE_ID,
        'SAVE_URL' => '/bitrix/admin/yamarket_promo_run.php?lang=' . LANGUAGE_ID . '&id=#ID#',
		'PROVIDER_TYPE' => 'Promo',
		'MODEL_CLASS_NAME' => Market\Export\Promo\Model::getClassName(),
		'USE_METRIKA' => 'Y',
		'CONTEXT_MENU' => $contextMenu,
		'TABS' => [
			[
				'name' => Market\Config::getLang('PROMO_EDIT_TAB_COMMON'),
				'fields' => [
					'ACTIVE',
					'NAME',
					'PROMO_TYPE',
					'PROMO_GIFT_IBLOCK_ID',
					'URL',
					'SETUP_EXPORT_ALL',
					'SETUP',
					'DESCRIPTION'
				],
				'data' => [
					'NOTE' => Market\Config::getLang('PROMO_EDIT_DOCUMENTATION_TITLE'),
					'NOTE_DESCRIPTION' => Market\Config::getLang('PROMO_EDIT_DOCUMENTATION_DESCRIPTION'),
				]
			],
			[
				'name' => Market\Config::getLang('PROMO_EDIT_TAB_RULE'),
                'layout' => 'promo-discount',
				'fields' => [
					'START_DATE',
					'FINISH_DATE',
					'DISCOUNT_UNIT',
					'DISCOUNT_CURRENCY',
					'DISCOUNT_VALUE',
					'PROMO_CODE',
					'GIFT_REQUIRED_QUANTITY',
					'GIFT_FREE_QUANTITY',
					'EXTERNAL_ID',
					'PROMO_PRODUCT',
					'PROMO_GIFT'
				]
			],
		]
	]);
}

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php';
