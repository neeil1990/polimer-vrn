<?php

/** @global CMain $APPLICATION */
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Config\Option;

$accessLevel = (string)$APPLICATION->GetGroupRight('yandex.market');

if ($accessLevel > 'D')
{
	Loc::loadMessages(__FILE__);

	$yaMenu = [
		[
			'parent_menu' => 'global_menu_services',
			'section' => 'yamarket_turbo',
			'sort' => 1000,
			'text' => Loc::getMessage('YANDEX_MARKET_MENU_TURBO_ROOT'),
			'title' => Loc::getMessage('YANDEX_MARKET_MENU_TURBO_ROOT'),
			'icon' => 'yamarket_turbo_icon',
			'items_id' => 'menu_yamarket_turbo',
			'items' => [
				[
					'text' => Loc::getMessage('YANDEX_MARKET_MENU_SETUP'),
					'title' => Loc::getMessage('YANDEX_MARKET_MENU_SETUP'),
					'url' => 'yamarket_setup_list.php?lang='.LANGUAGE_ID . '&service=turbo&find_group=0&set_filter=Y&apply_filter=Y',
					'more_url' => [
						'yamarket_setup_list.php?lang='.LANGUAGE_ID.'&service=turbo',
						'yamarket_setup_edit.php?lang='.LANGUAGE_ID.'&service=turbo',
						'yamarket_setup_group_edit.php?lang='.LANGUAGE_ID.'&service=turbo',
						'yamarket_setup_run.php?lang='.LANGUAGE_ID.'&service=turbo',
						'yamarket_log.php?lang='.LANGUAGE_ID.'&service=turbo',
					],
					'rights' => 'PE',
				],
				[
					'text' => Loc::getMessage('YANDEX_MARKET_MENU_PROMO'),
					'title' => Loc::getMessage('YANDEX_MARKET_MENU_PROMO'),
					'url' => 'yamarket_promo_list.php?lang='.LANGUAGE_ID,
					'more_url' => [
						'yamarket_promo_list.php',
						'yamarket_promo_edit.php',
						'yamarket_promo_run.php',
						'yamarket_promo_result.php',
					],
					'rights' => 'PE',
				],
				[
					'text' => Loc::getMessage('YANDEX_MARKET_MENU_ORDER_ADMIN'),
					'title' => Loc::getMessage('YANDEX_MARKET_MENU_ORDER_ADMIN'),
					'url' => 'yamarket_trading_order_admin.php?lang='.LANGUAGE_ID . '&service=turbo',
					'more_url' => [],
					'rights' => 'PT',
					'hidden' => (Option::get('yandex.market', 'menu_turbo_trading', 'N') !== 'Y'),
				],
				[
					'text' => Loc::getMessage('YANDEX_MARKET_MENU_SETTINGS'),
					'title' => Loc::getMessage('YANDEX_MARKET_MENU_SETTINGS'),
					'url' => 'yamarket_trading_list.php?lang='.LANGUAGE_ID . '&service=turbo',
					'more_url' => [
						'yamarket_trading_setup.php?lang='.LANGUAGE_ID . '&service=turbo',
						'yamarket_trading_edit.php?lang='.LANGUAGE_ID . '&service=turbo',
					],
					'hidden' => (Option::get('yandex.market', 'menu_turbo_trading', 'N') !== 'Y'),
				],
				[
					'text' => Loc::getMessage('YANDEX_MARKET_MENU_EVENT'),
					'title' => Loc::getMessage('YANDEX_MARKET_MENU_EVENT'),
					'url' => 'yamarket_trading_log.php?lang='.LANGUAGE_ID . '&service=turbo',
					'more_url' => [],
					'hidden' => (Option::get('yandex.market', 'menu_turbo_trading', 'N') !== 'Y'),
				],
				[
					'text' => Loc::getMessage('YANDEX_MARKET_MENU_CONFIRMATION'),
					'title' => Loc::getMessage('YANDEX_MARKET_MENU_CONFIRMATION'),
					'url' => 'yamarket_confirmation_list.php?lang='.LANGUAGE_ID,
					'more_url' => [
						'yamarket_confirmation_list.php',
						'yamarket_confirmation_edit.php',
					]
				],
				[
					'text' => Loc::getMessage('YANDEX_MARKET_MENU_HELP'),
					'title' => Loc::getMessage('YANDEX_MARKET_MENU_HELP'),
					'url' => 'javascript:window.open("https://yandex.ru/support/turbo-module-1c-bitrix/", "_blank");void(0);',
					'more_url' => [],
					'rights' => 'D', // any
				]
			],
		],
		[
			'parent_menu' => 'global_menu_services',
			'section' => 'yamarket_marketplace',
			'sort' => 1005,
			'text' => Loc::getMessage('YANDEX_MARKET_MENU_MARKETPLACE_ROOT'),
			'title' => Loc::getMessage('YANDEX_MARKET_MENU_MARKETPLACE_ROOT'),
			'icon' => 'yamarket_origin_icon',
			'items_id' => 'menu_yamarket_marketplace',
			'items' => [
				[
					'text' => Loc::getMessage('YANDEX_MARKET_MENU_ORDER_ADMIN'),
					'title' => Loc::getMessage('YANDEX_MARKET_MENU_ORDER_ADMIN'),
					'url' => 'yamarket_trading_order_admin.php?lang='.LANGUAGE_ID . '&service=marketplace',
					'more_url' => [
						'yamarket_trading_order_admin.php?lang='.LANGUAGE_ID . '&service=beru',
					],
					'rights' => 'PT',
					'hidden' => (Option::get('yandex.market', 'menu_multi_behavior_trading', 'N') === 'Y'),
				],
				[
					'text' => Loc::getMessage('YANDEX_MARKET_MENU_ORDER_ADMIN_FBS'),
					'title' => Loc::getMessage('YANDEX_MARKET_MENU_ORDER_ADMIN_FBS'),
					'url' => 'yamarket_trading_order_admin.php?lang='.LANGUAGE_ID . '&service=marketplace&behavior=default',
					'more_url' => [
						'yamarket_trading_order_admin.php?lang='.LANGUAGE_ID . '&service=beru',
					],
					'rights' => 'PT',
					'hidden' => (Option::get('yandex.market', 'menu_multi_behavior_trading', 'N') !== 'Y'),
				],
				[
					'text' => Loc::getMessage('YANDEX_MARKET_MENU_ORDER_ADMIN_DBS'),
					'title' => Loc::getMessage('YANDEX_MARKET_MENU_ORDER_ADMIN_DBS'),
					'url' => 'yamarket_trading_order_admin.php?lang='.LANGUAGE_ID . '&service=marketplace&behavior=dbs',
					'more_url' => [
						'yamarket_trading_order_admin.php?lang='.LANGUAGE_ID . '&service=beru',
					],
					'rights' => 'PT',
					'hidden' => (Option::get('yandex.market', 'menu_multi_behavior_trading', 'N') !== 'Y'),
				],
				[
					'text' => Loc::getMessage('YANDEX_MARKET_MENU_ORDER_LIST'),
					'title' => Loc::getMessage('YANDEX_MARKET_MENU_ORDER_LIST'),
					'url' => 'yamarket_trading_order_list.php?lang='.LANGUAGE_ID . '&service=marketplace',
					'more_url' => [
						'yamarket_trading_order_list.php?lang='.LANGUAGE_ID . '&service=beru',
					],
					'rights' => 'PT',
				],
				[
					'text' => Loc::getMessage('YANDEX_MARKET_MENU_SHIPMENT_LIST'),
					'title' => Loc::getMessage('YANDEX_MARKET_MENU_SHIPMENT_LIST'),
					'url' => 'yamarket_trading_shipment_list.php?lang='.LANGUAGE_ID . '&service=marketplace',
					'more_url' => [],
					'rights' => 'PT',
					'hidden' => (Option::get('yandex.market', 'menu_logistic', 'N') !== 'Y'),
				],
				[
					'text' => Loc::getMessage('YANDEX_MARKET_MENU_SETTINGS'),
					'title' => Loc::getMessage('YANDEX_MARKET_MENU_SETTINGS'),
					'url' => 'yamarket_trading_list.php?lang='.LANGUAGE_ID . '&service=marketplace',
					'more_url' => [
						'yamarket_trading_edit.php?lang='.LANGUAGE_ID . '&service=beru',
						'yamarket_trading_setup.php?lang='.LANGUAGE_ID . '&service=marketplace',
						'yamarket_trading_edit.php?lang='.LANGUAGE_ID . '&service=marketplace',
						'yamarket_trading_import.php?lang='.LANGUAGE_ID . '&service=marketplace',
					],
				],
				[
					'text' => Loc::getMessage('YANDEX_MARKET_MENU_EVENT'),
					'title' => Loc::getMessage('YANDEX_MARKET_MENU_EVENT'),
					'url' => 'yamarket_trading_log.php?lang='.LANGUAGE_ID . '&service=marketplace',
					'more_url' => [
						'yamarket_trading_log.php?lang='.LANGUAGE_ID . '&service=beru',
					],
				],
				[
					'text' => Loc::getMessage('YANDEX_MARKET_MENU_HELP'),
					'title' => Loc::getMessage('YANDEX_MARKET_MENU_HELP'),
					'url' => 'javascript:window.open("https://yandex.ru/support/marketplace-module-1c-bitrix/", "_blank");void(0);',
					'more_url' => [],
					'rights' => 'PT',
				]
			],
		],
		[
			'parent_menu' => 'global_menu_services',
			'section' => 'yamarket_promotion',
			'sort' => 1010,
			'text' => Loc::getMessage('YANDEX_MARKET_MENU_PROMOTION'),
			'title' => Loc::getMessage('YANDEX_MARKET_MENU_PROMOTION'),
			'icon' => 'yamarket_promotion_icon',
			'items_id' => 'menu_yamarket_promotion',
			'items' => [
				[
					'text' => Loc::getMessage('YANDEX_MARKET_MENU_SALES_BOOST_SETUP'),
					'title' => Loc::getMessage('YANDEX_MARKET_MENU_SALES_BOOST_SETUP'),
					'url' => 'yamarket_sales_boost_list.php?lang='.LANGUAGE_ID,
					'more_url' => [
						'yamarket_sales_boost_edit.php?lang='.LANGUAGE_ID,
						'yamarket_sales_boost_run.php?lang='.LANGUAGE_ID,
					],
					'rights' => 'PT',
				],
				[
					'text' => Loc::getMessage('YANDEX_MARKET_MENU_SALES_BOOST_BIDS'),
					'title' => Loc::getMessage('YANDEX_MARKET_MENU_SALES_BOOST_BIDS'),
					'url' => 'yamarket_sales_boost_bids.php?lang='.LANGUAGE_ID,
					'rights' => 'PT',
				],
			],
		],
		[
			'parent_menu' => 'global_menu_services',
			'section' => 'yamarket_origin',
			'sort' => 1015,
			'text' => Loc::getMessage('YANDEX_MARKET_MENU_ORIGIN_ROOT'),
			'title' => Loc::getMessage('YANDEX_MARKET_MENU_ORIGIN_ROOT'),
			'icon' => 'yamarket_assortment_icon',
			'items_id' => 'menu_yamarket',
			'items' => [
				[
					'text' => Loc::getMessage('YANDEX_MARKET_MENU_SETUP'),
					'title' => Loc::getMessage('YANDEX_MARKET_MENU_SETUP'),
					'url' => 'yamarket_setup_list.php?lang=' . LANGUAGE_ID . '&find_group=0&set_filter=Y&apply_filter=Y',
					'more_url' => [
						'yamarket_setup_list.php',
						'yamarket_setup_edit.php',
						'yamarket_setup_group_edit.php',
						'yamarket_setup_run.php',
						'yamarket_migration.php',
						'yamarket_checker.php',
					],
					'rights' => 'PE',
				],
				[
					'text' => Loc::getMessage('YANDEX_MARKET_MENU_COLLECTION'),
					'title' => Loc::getMessage('YANDEX_MARKET_MENU_COLLECTION'),
					'url' => 'yamarket_collection_list.php?lang='.LANGUAGE_ID,
					'more_url' => [
						'yamarket_collection_edit.php?lang='.LANGUAGE_ID,
						'yamarket_collection_run.php?lang='.LANGUAGE_ID,
						'yamarket_collection_result.php?lang='.LANGUAGE_ID,
					],
					'hidden' => (Option::get('yandex.market', 'experiment_collection', 'N') !== 'Y'),
				],
				[
					'text' => Loc::getMessage('YANDEX_MARKET_MENU_LOG'),
					'title' => Loc::getMessage('YANDEX_MARKET_MENU_LOG'),
					'url' => 'yamarket_log.php?lang='.LANGUAGE_ID,
					'more_url' => [],
					'rights' => 'PE',
				],
				[
					'text' => Loc::getMessage('YANDEX_MARKET_MENU_HELP'),
					'title' => Loc::getMessage('YANDEX_MARKET_MENU_HELP'),
					'url' => 'javascript:window.open("https://yandex.ru/support/market-cms/", "_blank");void(0);',
					'more_url' => [],
					'rights' => 'PE',
				]
			]
		],
	];

	// filter items by access rights

	foreach ($yaMenu as $yaRootLevelKey => &$yaRootLevel)
	{
		foreach ($yaRootLevel['items'] as $yaItemKey => $yaItem)
		{
			// hidden

			if (!empty($yaItem['hidden']))
			{
				unset($yaRootLevel['items'][$yaItemKey]);
				continue;
			}

			// access

			$yaItemRights = isset($yaItem['rights']) ? $yaItem['rights'] : 'R';

			if ($accessLevel[0] < $yaItemRights[0])
			{
				$isMatchModuleRights = false;
			}
			else if ($accessLevel[0] > $yaItemRights[0])
			{
				$isMatchModuleRights = true;
			}
			else
			{
				$isMatchModuleRights = ($accessLevel === $yaItemRights);
			}

			if (!$isMatchModuleRights)
			{
				unset($yaRootLevel['items'][$yaItemKey]);
			}
		}

		if (empty($yaRootLevel['items']))
		{
			unset($yaMenu[$yaRootLevelKey]);
		}
	}
	unset($yaRootLevel);

	return $yaMenu;
}
else
{
	return false;
}