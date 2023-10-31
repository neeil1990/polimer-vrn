<?php

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Yandex\Market;

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin.php';

global $APPLICATION;

Loc::loadMessages(__FILE__);

if (!Main\Loader::includeModule('yandex.market'))
{
	\CAdminMessage::ShowMessage([
        'TYPE' => 'ERROR',
        'MESSAGE' => Loc::getMessage('YANDEX_MARKET_SALES_BOOST_EDIT_REQUIRE_MODULE')
    ]);
}
else if (!Market\Ui\Access::isProcessTradingAllowed())
{
	\CAdminMessage::ShowMessage([
		'TYPE' => 'ERROR',
		'MESSAGE' => Loc::getMessage('YANDEX_MARKET_SALES_BOOST_EDIT_ACCESS_DENIED')
	]);
}
else
{
	$id = null;
	$contextMenu = [
        [
            'ICON' => 'btn_list',
            'LINK' => '/bitrix/admin/yamarket_sales_boost_list.php?lang=' . LANGUAGE_ID,
            'TEXT' => Market\Config::getLang('SALES_BOOST_EDIT_CONTEXT_MENU_LIST')
        ]
    ];

	if (!empty($_GET['id']))
    {
        $id = (int)$_GET['id'];

        $contextMenu[] = [
            'LINK' => '/bitrix/admin/yamarket_trading_log.php?' .http_build_query([
				'lang' => LANGUAGE_ID,
				'service' => Market\Ui\Service\Manager::TYPE_MARKETPLACE,
				'find_audit' => Market\Logger\Trading\Audit::SALES_BOOST,
				'find_level' => 'error',
	            'set_filter' => 'Y',
	            'apply_filter' => 'Y',
            ]),
            'TEXT' => Market\Config::getLang('SALES_BOOST_EDIT_CONTEXT_MENU_LOG')
        ];
    }

	$APPLICATION->IncludeComponent('yandex.market:admin.form.edit', '', [
		'TITLE' => Market\Config::getLang('SALES_BOOST_EDIT_TITLE_EDIT'),
		'TITLE_ADD' => Market\Config::getLang('SALES_BOOST_EDIT_TITLE_ADD'),
		'BTN_SAVE' => Market\Config::getLang('SALES_BOOST_EDIT_BTN_SAVE'),
		'FORM_ID'   => 'YANDEX_MARKET_ADMIN_SALES_BOOST_EDIT',
		'FORM_BEHAVIOR' => 'steps',
		'ALLOW_SAVE' => Market\Ui\Access::isWriteAllowed(),
		'PRIMARY' => $id,
		'COPY' => isset($_GET['copy']) && $_GET['copy'] === 'Y',
		'LIST_URL' => '/bitrix/admin/yamarket_sales_boost_list.php?lang=' . LANGUAGE_ID,
        'SAVE_URL' => '/bitrix/admin/yamarket_sales_boost_run.php?lang=' . LANGUAGE_ID . '&id=#ID#',
		'PROVIDER_TYPE' => 'SalesBoost',
		'MODEL_CLASS_NAME' => Market\SalesBoost\Setup\Model::class,
		'USE_METRIKA' => 'Y',
		'CONTEXT_MENU' => $contextMenu,
		'TABS' => [
			[
				'name' => Market\Config::getLang('SALES_BOOST_EDIT_TAB_COMMON'),
				'fields' => [
					'ACTIVE',
					'NAME',
					'START_DATE',
					'FINISH_DATE',
					'SORT',
					'BUSINESS',
				],
			],
			[
				'name' => Market\Config::getLang('SALES_BOOST_EDIT_TAB_RULE'),
				'layout' => 'product-filter',
				'fields' => [
					'BID_FORMAT',
					'BID_DEFAULT',
					'BID_FIELD',
					'SALES_BOOST_PRODUCT',
				],
			],
		],
		'PRODUCT_FILTER_FIELDS' => [
			'SALES_BOOST_PRODUCT',
		],
	]);
}

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php';
