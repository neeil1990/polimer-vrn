<?php

namespace Yandex\Market\Ui;

use Bitrix\Main;
use Yandex\Market;

Main\Localization\Loc::loadMessages(__FILE__);

class Options extends Market\Ui\Reference\Page
{
	public function show()
	{
		global $APPLICATION;

		$tabs = $this->getTabs();
		$fields = $this->getOptions();
		$writeRights = $this->getWriteRights();

		$APPLICATION->IncludeComponent('yandex.market:admin.form.edit', '', [
			'FORM_ID' => 'YANDEX_MARKET_ADMIN_OPTIONS',
			'PROVIDER_TYPE' => 'Options',
			'PRIMARY' => 1, // force load options
			'TABS' => $tabs,
			'FIELDS' => $fields,
			'ALLOW_SAVE' => $this->isAuthorized($writeRights),
			'BUTTONS' => [
				[ 'BEHAVIOR' => 'save' ],
				[ 'BEHAVIOR' => 'reset' ],
			],
		]);
	}

	protected function getTabs()
	{
		return [
			'COMMON' => [
				'name' => Market\Config::getLang('OPTIONS_TAB_OPTIONS'),
			],
			'TRADING' => [
				'name' => Market\Config::getLang('OPTIONS_TAB_TRADING'),
			],
			'SALES_BOOST' => [
				'name' => Market\Config::getLang('OPTIONS_TAB_SALES_BOOST'),
			],
			'PERMISSIONS' => [
				'name' => Market\Config::getLang('OPTIONS_TAB_PERMISSIONS'),
				'fields' => [
					'PERMISSIONS',
				]
			],
		];
	}

	public function getOptions()
	{
		return
			$this->getExportOptions()
			+ $this->getCurrencyOptions()
			+ $this->getPromoOptions()
			+ $this->getCatalogOptions()
			+ $this->getAdditionalOptions()
			+ $this->getUserPhoneOptions()
			+ $this->getTradingOrderOptions()
			+ $this->getTradingBasketOptions()
			+ $this->getTradingLogOptions()
			+ $this->getTradingTaxSystemOptions()
			+ $this->getTradingServerOptions()
			+ $this->getTradingStocksOptions()
			+ $this->getTradingListenOptions()
			+ $this->getTradingPushOptions()
			+ $this->getTradingExperimentOptions()
			+ $this->getServerStampOptions()
			+ $this->getSalesBoostRefreshOptions()
		;
	}

	protected function getExportOptions()
	{
		$isAgentCli = Market\Utils::isAgentUseCron();

		return [
			'export_run_offer_page_size' => [
				'TYPE' => 'integer',
				'GROUP' => Market\Config::getLang('UI_OPTION_GROUP_EXPORT'),
				'NAME' => Market\Config::getLang('UI_OPTION_EXPORT_OFFER_PAGE_SIZE'),
				'HELP_MESSAGE' => Market\Config::getLang('UI_OPTION_EXPORT_OFFER_PAGE_SIZE_HINT'),
				'SETTINGS' => [
					'DEFAULT_VALUE' => 50,
					'MIN_VALUE' => 1,
				]
			],
			'export_run_agent_changes_limit' => [
				'TYPE' => 'integer',
				'GROUP' => Market\Config::getLang('UI_OPTION_GROUP_EXPORT'),
				'NAME' => Market\Config::getLang('UI_OPTION_EXPORT_AGENT_CHANGES_LIMIT'),
				'HELP_MESSAGE' => Market\Config::getLang('UI_OPTION_EXPORT_AGENT_CHANGES_LIMIT_HINT'),
				'SETTINGS' => [
					'DEFAULT_VALUE' => 1000,
					'MIN_VALUE' => 1,
				]
			],
			'export_run_agent_time_limit_cli' => [
				'TYPE' => 'integer',
				'GROUP' => Market\Config::getLang('UI_OPTION_GROUP_EXPORT'),
				'NAME' => Market\Config::getLang('UI_OPTION_EXPORT_AGENT_TIME_LIMIT'),
				'HELP_MESSAGE' => Market\Config::getLang('UI_OPTION_EXPORT_AGENT_TIME_LIMIT_HINT'),
				'HIDDEN' => !$isAgentCli,
				'SETTINGS' => [
					'DEFAULT_VALUE' => 30,
					'MIN_VALUE' => 1,
					'MAX_VALUE' => 580,
				]
			],
			'export_run_agent_time_limit' => [
				'TYPE' => 'integer',
				'GROUP' => Market\Config::getLang('UI_OPTION_GROUP_EXPORT'),
				'NAME' => Market\Config::getLang('UI_OPTION_EXPORT_AGENT_TIME_LIMIT'),
				'HELP_MESSAGE' => Market\Config::getLang('UI_OPTION_EXPORT_AGENT_TIME_LIMIT_HINT'),
				'HIDDEN' => $isAgentCli,
				'SETTINGS' => [
					'DEFAULT_VALUE' => 5,
					'MIN_VALUE' => 1,
					'MAX_VALUE' => 50,
				]
			],
			'export_log_level' => [
				'TYPE' => 'enumeration',
				'GROUP' => Market\Config::getLang('UI_OPTION_GROUP_EXPORT'),
				'NAME' => Market\Config::getLang('UI_OPTION_EXPORT_LOG_LEVEL'),
				'VALUES' => [
					[
						'ID' => Market\Logger\Level::ERROR,
						'VALUE' => Market\Config::getLang('UI_OPTION_EXPORT_LOG_LEVEL_ERROR'),
					],
					[
						'ID' => Market\Logger\Level::WARNING,
						'VALUE' => Market\Config::getLang('UI_OPTION_EXPORT_LOG_LEVEL_WARNING'),
					],
				],
				'SETTINGS' => [
					'DEFAULT_VALUE' => Market\Logger\Level::WARNING,
					'ALLOW_NO_VALUE' => 'N',
				],
			],
			'export_writer_memory' => [
				'TYPE' => 'boolean',
				'GROUP' => Market\Config::getLang('UI_OPTION_GROUP_EXPORT'),
				'NAME' => Market\Config::getLang('UI_OPTION_EXPORT_WRITER_MEMORY'),
			],
			'export_writer_index' => [
				'TYPE' => 'boolean',
				'GROUP' => Market\Config::getLang('UI_OPTION_GROUP_EXPORT'),
				'NAME' => Market\Config::getLang('UI_OPTION_EXPORT_WRITER_INDEX'),
				'SETTINGS' => [
					'DEFAULT_VALUE' => 'Y',
				],
			],
		];
	}

	protected function getCatalogOptions()
	{
		$hasCatalog = Main\ModuleManager::isModuleInstalled('catalog');

		return [
			'export_catalog_price_discount_properties_optimize' => [
				'TYPE' => 'boolean',
				'GROUP' => Market\Config::getLang('UI_OPTION_GROUP_CATALOG'),
				'NAME' => Market\Config::getLang('UI_OPTION_EXPORT_CATALOG_PRICE_DISCOUNT_PROPERTIES_OPTIMIZE'),
				'HELP_MESSAGE' => Market\Config::getLang('UI_OPTION_EXPORT_CATALOG_PRICE_DISCOUNT_PROPERTIES_OPTIMIZE_HINT'),
				'HIDDEN' => !$hasCatalog,
				'SETTINGS' => [
					'DEFAULT_VALUE' => 'Y',
				],
			],
			'export_entity_catalog_use_short' => [
				'TYPE' => 'boolean',
				'GROUP' => Market\Config::getLang('UI_OPTION_GROUP_CATALOG'),
				'NAME' => Market\Config::getLang('UI_OPTION_EXPORT_CATALOG_USE_SHORT'),
				'HELP_MESSAGE' => Market\Config::getLang('UI_OPTION_EXPORT_CATALOG_USE_SHORT_HINT'),
				'HIDDEN' => !$hasCatalog || !Market\Export\Entity\Catalog\Provider::supportCatalogShortFields(),
				'SETTINGS' => [
					'DEFAULT_VALUE' => 'Y',
				],
			],
			'export_offer_catalog_type_compatibility' => [
				'TYPE' => 'enumeration',
				'GROUP' => Market\Config::getLang('UI_OPTION_GROUP_CATALOG'),
				'NAME' => Market\Config::getLang('UI_OPTION_EXPORT_CATALOG_TYPE_COMPATIBILITY'),
				'HELP_MESSAGE' => Market\Config::getLang('UI_OPTION_EXPORT_CATALOG_TYPE_COMPATIBILITY_HINT'),
				'VALUES' => [
					[ 'ID' => 'N', 'VALUE' => Market\Config::getLang('UI_OPTION_VALUE_N') ],
					[ 'ID' => 'Y', 'VALUE' => Market\Config::getLang('UI_OPTION_VALUE_Y') ],
				],
				'HIDDEN' => !$hasCatalog,
			],
			'export_entity_catalog_sku_available_auto' => [
				'TYPE' => 'enumeration',
				'GROUP' => Market\Config::getLang('UI_OPTION_GROUP_CATALOG'),
				'NAME' => Market\Config::getLang('UI_OPTION_EXPORT_CATALOG_SKU_AVAILABLE_AUTO'),
				'HELP_MESSAGE' => Market\Config::getLang('UI_OPTION_EXPORT_CATALOG_SKU_AVAILABLE_AUTO_HINT'),
				'VALUES' => [
					[ 'ID' => 'N', 'VALUE' => Market\Config::getLang('UI_OPTION_VALUE_N') ],
					[ 'ID' => 'Y', 'VALUE' => Market\Config::getLang('UI_OPTION_VALUE_Y') ],
				],
				'HIDDEN' => !$hasCatalog,
			],
		];
	}

	protected function getCurrencyOptions()
	{
		return [
			'export_currency_rate' => [
				'TYPE' => 'boolean',
				'GROUP' => Market\Config::getLang('UI_OPTION_GROUP_CURRENCY'),
				'NAME' => Market\Config::getLang('UI_OPTION_EXPORT_CURRENCY_RATE'),
			],
		];
	}

	protected function getPromoOptions()
	{
		$hasCatalog = Main\ModuleManager::isModuleInstalled('catalog');
		$hasSale = Main\ModuleManager::isModuleInstalled('sale');

		return [
			'export_promo_discount_external_gift' => [
				'TYPE' => 'boolean',
				'GROUP' => Market\Config::getLang('UI_OPTION_GROUP_PROMO'),
				'NAME' => Market\Config::getLang('UI_OPTION_EXPORT_PROMO_DISCOUNT_EXTERNAL_GIFT'),
				'HELP_MESSAGE' => Market\Config::getLang('UI_OPTION_EXPORT_PROMO_DISCOUNT_EXTERNAL_GIFT_HINT'),
				'HIDDEN' => !$hasCatalog && !$hasSale,
				'SETTINGS' => [
					'DEFAULT_VALUE' => 'Y',
				],
			],
		];
	}

	protected function getAdditionalOptions()
	{
		return [
			'expert_mode' => [
				'TYPE' => 'boolean',
				'GROUP' => Market\Config::getLang('UI_OPTION_GROUP_ADDITIONAL'),
				'NAME' => Market\Config::getLang('UI_OPTION_EXPERT_MODE'),
				'SETTINGS' => [
					'DEFAULT_VALUE' => 'N',
				],
			],
			'experiment_collection' => [
				'TYPE' => 'boolean',
				'NAME' => Market\Config::getLang('UI_OPTION_EXPERIMENT_COLLECTION'),
				'SETTINGS' => [
					'DEFAULT_VALUE' => 'N',
				],
			],
		];
	}

	protected function getUserPhoneOptions()
	{
		return [
			'phone_mask_rule' => [
				'TYPE' => 'enumeration',
				'TAB' => 'TRADING',
				'NAME' => Market\Config::getLang('UI_OPTION_PHONE_MASK_RULE'),
				'VALUES' => $this->getPhoneMaskRuleEnum(),
				'SETTINGS' => [
					'CAPTION_NO_VALUE' => Market\Config::getLang('UI_OPTION_PHONE_MASK_RULE_NO_VALUE'),
				],
			],
			'phone_mask' => [
				'TYPE' => 'string',
				'TAB' => 'TRADING',
				'NAME' => Market\Config::getLang('UI_OPTION_PHONE_MASK'),
				'DEPEND' => [
					'phone_mask_rule' => [
						'RULE' => 'ANY',
						'VALUE' => 'custom',
					],
				]
			],
			'user_phone_field' => [
				'TYPE' => 'enumeration',
				'TAB' => 'TRADING',
				'NAME' => Market\Config::getLang('UI_OPTION_USER_PHONE_FIELD'),
				'VALUES' => $this->getUserPhoneFieldEnum(),
				'SETTINGS' => [
					'DEFAULT_VALUE' => 'PERSONAL_MOBILE',
					'ALLOW_NO_VALUE' => 'N',
				]
			]
		];
	}

	protected function getPhoneMaskRuleEnum()
	{
		$result = [];

		// default formats

		$variants = Market\Data\Phone::getFormatVariants();

		foreach ($variants as $variant)
		{
			$result[] = [
				'ID' => $variant,
				'VALUE' => Market\Data\Phone::getMask($variant),
			];
		}

		// custom

		$result[] = [
			'ID' => Market\Data\Phone::FORMAT_CUSTOM,
			'VALUE' => Market\Config::getLang('UI_OPTION_PHONE_MASK_RULE_CUSTOM'),
		];

		return $result;
	}

	protected function getUserPhoneFieldEnum()
	{
		global $USER_FIELD_MANAGER;

		$result = [];

		// default user fields

		$defaultFields = [
			'PERSONAL_PHONE',
			'PERSONAL_MOBILE',
			'WORK_PHONE',
		];

		foreach ($defaultFields as $field)
		{
			$result[] = [
				'ID' => $field,
				'VALUE' => Market\Config::getLang('UI_OPTION_USER_PHONE_FIELD_' . $field, null, $field),
			];
		}

		// user fields

		$userFields = $USER_FIELD_MANAGER->GetUserFields('USER');

		foreach ($userFields as $fieldName => $userField)
		{
			if (
				Market\Data\TextString::getPosition($fieldName, 'PHONE') !== false
				|| Market\Data\TextString::getPosition($fieldName, 'TEL') !== false
			)
			{
				$result[] = [
					'ID' => $fieldName,
					'VALUE' => $fieldName,
				];
			}
		}

		return $result;
	}

	protected function getPermissions()
	{
		return [
			'PERMISSIONS' => [
				'TYPE' => 'string',
			],
		];
	}

	protected function getTradingOrderOptions()
	{
		return [
			'trading_order_use_id' => [
				'TYPE' => 'boolean',
				'TAB' => 'TRADING',
				'NAME' => Market\Config::getLang('UI_OPTION_TRADING_ORDER_USE_ID'),
			],
		];
	}

	protected function getTradingBasketOptions()
	{
		return [
			'trading_basket_name_original' => [
				'TYPE' => 'boolean',
				'TAB' => 'TRADING',
				'NAME' => Market\Config::getLang('UI_OPTION_TRADING_BASKET_NAME_ORIGINAL'),
				'HELP_MESSAGE' => Market\Config::getLang('UI_OPTION_TRADING_BASKET_NAME_ORIGINAL_HELP'),
			],
		];
	}

	protected function getTradingLogOptions()
	{
		return [
			'trading_log_tracing' => [
				'TYPE' => 'boolean',
				'TAB' => 'TRADING',
				'NAME' => Market\Config::getLang('UI_OPTION_TRADING_LOG_TRACING'),
				'SETTINGS' => [
					'DEFAULT_VALUE' => 'Y',
				],
			],
		];
	}

	protected function getTradingTaxSystemOptions()
	{
		return [
			'trading_use_tax_system' => [
				'TYPE' => 'boolean',
				'TAB' => 'TRADING',
				'NAME' => Market\Config::getLang('UI_OPTION_TRADING_USE_TAX_SYSTEM'),
			],
		];
	}

	protected function getTradingServerOptions()
	{
		return [
			'ddos_guard' => [
				'TYPE' => 'boolean',
				'TAB' => 'TRADING',
				'NAME' => Market\Config::getLang('UI_OPTION_TRADING_DDOS_GUARD'),
			],
		];
	}

	protected function getTradingStocksOptions()
	{
		return [
			'trading_auto_product_offer' => [
				'TYPE' => 'boolean',
				'TAB' => 'TRADING',
				'GROUP' => Market\Config::getLang('UI_OPTION_GROUP_TRADING_PRODUCT'),
				'NAME' => Market\Config::getLang('UI_OPTION_TRADING_AUTO_PRODUCT_OFFER'),
				'HELP_MESSAGE' => Market\Config::getLang('UI_OPTION_TRADING_AUTO_PRODUCT_OFFER_HELP'),
				'SETTINGS' => [
					'DEFAULT_VALUE' => 'Y',
				],
			],
			'trading_reserve_days' => [
				'TYPE' => 'number',
				'TAB' => 'TRADING',
				'GROUP' => Market\Config::getLang('UI_OPTION_GROUP_TRADING_PRODUCT'),
				'NAME' => Market\Config::getLang('UI_OPTION_TRADING_RESERVE_DAYS'),
				'SETTINGS' => [
					'DEFAULT_VALUE' => 7,
					'MIN_VALUE' => 1,
					'UNIT' => Market\Config::getLang('UI_OPTION_DAYS_UNIT'),
				]
			],
		];
	}

	protected function getTradingListenOptions()
	{
		return [
			'trading_silent_basket' => [
				'TYPE' => 'boolean',
				'TAB' => 'TRADING',
				'GROUP' => Market\Config::getLang('UI_OPTION_GROUP_TRADING_LISTEN'),
				'NAME' => Market\Config::getLang('UI_OPTION_TRADING_SILENT_BASKET'),
			],
		];
	}

	protected function getTradingPushOptions()
	{
		$isAgentCli = Market\Utils::isAgentUseCron();

		return [
			Market\Trading\State\PushAgent::optionName('period_restart') => [
				'TYPE' => 'integer',
				'TAB' => 'TRADING',
				'GROUP' => Market\Config::getLang('UI_OPTION_GROUP_TRADING_PUSH'),
				'NAME' => Market\Config::getLang('UI_OPTION_TRADING_PUSH_PERIOD_RESTART'),
				'HELP_MESSAGE' => Market\Config::getLang('UI_OPTION_TRADING_PUSH_PERIOD_RESTART_HELP'),
				'SETTINGS' => [
					'DEFAULT_VALUE' => 300,
					'MIN_VALUE' => 1,
				],
			],
			Market\Trading\State\PushAgent::optionName('period_refresh') => [
				'TYPE' => 'integer',
				'TAB' => 'TRADING',
				'GROUP' => Market\Config::getLang('UI_OPTION_GROUP_TRADING_PUSH'),
				'NAME' => Market\Config::getLang('UI_OPTION_TRADING_PUSH_PERIOD_REFRESH'),
				'HELP_MESSAGE' => Market\Config::getLang('UI_OPTION_TRADING_PUSH_PERIOD_REFRESH_HELP'),
				'SETTINGS' => [
					'DEFAULT_VALUE' => 1800,
					'MIN_VALUE' => 1,
				],
			],
			Market\Trading\State\PushAgent::optionName('page_size') => [
				'TYPE' => 'integer',
				'TAB' => 'TRADING',
				'GROUP' => Market\Config::getLang('UI_OPTION_GROUP_TRADING_PUSH'),
				'NAME' => Market\Config::getLang('UI_OPTION_TRADING_PUSH_PAGE_SIZE'),
				'SETTINGS' => [
					'DEFAULT_VALUE' => 500,
					'MIN_VALUE' => 1,
				],
			],
			Market\Trading\State\PushAgent::optionName('time_limit') => [
				'TYPE' => 'integer',
				'TAB' => 'TRADING',
				'GROUP' => Market\Config::getLang('UI_OPTION_GROUP_TRADING_PUSH'),
				'NAME' => Market\Config::getLang('UI_OPTION_TRADING_PUSH_TIME_LIMIT'),
				'HELP_MESSAGE' => Market\Config::getLang('UI_OPTION_TRADING_PUSH_TIME_LIMIT_HINT'),
				'HIDDEN' => $isAgentCli,
				'SETTINGS' => [
					'DEFAULT_VALUE' => 5,
					'MIN_VALUE' => 1,
				],
			],
			Market\Trading\State\PushAgent::optionName('time_limit_cli') => [
				'TYPE' => 'integer',
				'TAB' => 'TRADING',
				'GROUP' => Market\Config::getLang('UI_OPTION_GROUP_TRADING_PUSH'),
				'NAME' => Market\Config::getLang('UI_OPTION_TRADING_PUSH_TIME_LIMIT'),
				'HELP_MESSAGE' => Market\Config::getLang('UI_OPTION_TRADING_PUSH_TIME_LIMIT_HINT'),
				'HIDDEN' => !$isAgentCli,
				'SETTINGS' => [
					'DEFAULT_VALUE' => 30,
					'MIN_VALUE' => 1,
				],
			],
			Market\Trading\State\PushAgent::optionName('expire_days') => [
				'TYPE' => 'number',
				'TAB' => 'TRADING',
				'GROUP' => Market\Config::getLang('UI_OPTION_GROUP_TRADING_PUSH'),
				'NAME' => Market\Config::getLang('UI_OPTION_TRADING_PUSH_EXPIRE_DAYS'),
				'HELP_MESSAGE' => Market\Config::getLang('UI_OPTION_TRADING_PUSH_EXPIRE_DAYS_HINT'),
				'SETTINGS' => [
					'DEFAULT_VALUE' => 7,
					'MIN_VALUE' => 1,
					'UNIT' => Market\Config::getLang('UI_OPTION_DAYS_UNIT'),
				],
			],
		];
	}

	protected function getTradingExperimentOptions()
	{
		return [
			'experiment_trading_sibling_reserved_stop' => [
				'TYPE' => 'boolean',
				'TAB' => 'TRADING',
				'GROUP' => Market\Config::getLang('UI_OPTION_GROUP_TRADING_EXPERIMENT'),
				'NAME' => Market\Config::getLang('UI_OPTION_EXPERIMENT_TRADING_SIBLING_RESERVED_STOP'),
			],
		];
	}

	protected function getServerStampOptions()
	{
		$result = [];
		$controller = new Market\Utils\ServerStamp\Controller();

		foreach ($controller->properties() as $property)
		{
			$name = $property->name();
			$title = $property->title();

			$result['server_stamp_disable_' . $name] = [
				'TYPE' => 'boolean',
				'TAB' => 'TRADING',
				'GROUP' => Market\Config::getLang('UI_OPTION_GROUP_SERVER_STAMP'),
				'NAME' => Market\Config::getLang('UI_OPTION_SERVER_STAMP_DISABLE_PROPERTY', [
					'#TITLE#' => $title,
				]),
			];
		}

		return $result;
	}

	protected function getSalesBoostRefreshOptions()
	{
		return [
			'sales_boost_auto_update' => [
				'TYPE' => 'boolean',
				'TAB' => 'SALES_BOOST',
				'NAME' => Market\Config::getLang('UI_OPTION_SALES_BOOST_AUTO_UPDATE'),
				'SETTINGS' => [
					'DEFAULT_VALUE' => 'Y',
				],
			],
			'sales_boost_refresh_period' => [
				'TYPE' => 'enumeration',
				'TAB' => 'SALES_BOOST',
				'EDIT_IN_LIST' => (Market\Utils::isAgentUseCron() ? 'Y' : 'N'),
				'NAME' => Market\Config::getLang('UI_OPTION_SALES_BOOST_REFRESH_PERIOD'),
				'NOTE' => Market\Utils::isAgentUseCron() ? null : Market\Config::getLang('UI_OPTION_SALES_BOOST_REFRESH_PERIOD_WARNING'),
				'VALUES' => array_reverse(array_map(static function($period) {
					return [
						'ID' => $period,
						'VALUE' => Market\Config::getLang('UI_OPTION_SALES_BOOST_REFRESH_PERIOD_' . $period),
					];
				}, [
					604800, // week
					259200, // three days
					86400, // one day
					43200, // half day
					21600, // six hours
					10800, // three hours
					7200, // two hours
					3600, // one hour
					1800, // half hour
				])),
				'SETTINGS' => [
					'DEFAULT_VALUE' => Market\Utils::isAgentUseCron() ? 86400 : null,
					'CAPTION_NO_VALUE' => Market\Config::getLang('UI_OPTION_SALES_BOOST_REFRESH_PERIOD_DISABLE'),
				],
			],
			'sales_boost_refresh_time' => [
				'TYPE' => 'time',
				'TAB' => 'SALES_BOOST',
				'NAME' => Market\Config::getLang('UI_OPTION_SALES_BOOST_REFRESH_TIME'),
				'DEPEND' => [
					'sales_boost_refresh_period' => [
						'RULE' => 'EMPTY',
						'VALUE' => false,
					],
				],
			],
		];
	}
}