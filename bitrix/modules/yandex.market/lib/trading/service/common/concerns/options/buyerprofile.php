<?php

namespace Yandex\Market\Trading\Service\Common\Concerns\Options;

use Yandex\Market;
use Bitrix\Main;
use Yandex\Market\Trading\Entity as TradingEntity;
use Yandex\Market\Trading\Service as TradingService;

class BuyerProfile
{
	use Market\Reference\Concerns\HasLang;

	protected static function includeMessages()
	{
		Main\Localization\Loc::loadMessages(__FILE__);
	}

	public static function getFields(TradingEntity\Reference\Environment $environment, $siteId)
	{
		return [
			'BUYER_PROFILE_RULE' => [
				'TYPE' => 'enumeration',
				'NAME' => static::getLang('TRADING_SERVICE_OPTION_BUYER_PROFILE_RULE'),
				'HELP_MESSAGE' => static::getLang('TRADING_SERVICE_OPTION_BUYER_PROFILE_RULE_HELP'),
				'VALUES' => static::getBuyerProfileRuleEnum(),
				'SORT' => 3400,
				'SETTINGS' => [
					'CAPTION_NO_VALUE' => static::getLang('TRADING_SERVICE_OPTION_BUYER_PROFILE_RULE_NO_VALUE'),
					'STYLE' => 'max-width: 450px;'
				],
			],
		];
	}

	public static function getProfileIdOverrides()
	{
		return [
			'NAME' => static::getLang('TRADING_SERVICE_OPTION_PROFILE_ID_WITH_BUYER'),
			'HELP_MESSAGE' => static::getLang('TRADING_SERVICE_OPTION_PROFILE_ID_WITH_BUYER_HELP'),
		];
	}

	protected static function getBuyerProfileRuleEnum()
	{
		$variants = [
			BuyerProfileInterface::BUYER_PROFILE_RULE_NEW,
			BuyerProfileInterface::BUYER_PROFILE_RULE_FIRST,
			BuyerProfileInterface::BUYER_PROFILE_RULE_MATCH_EMAIL,
			BuyerProfileInterface::BUYER_PROFILE_RULE_MATCH_PHONE,
			BuyerProfileInterface::BUYER_PROFILE_RULE_MATCH_NAME,
			BuyerProfileInterface::BUYER_PROFILE_RULE_MATCH_FULL,
		];
		$result = [];

		foreach ($variants as $variant)
		{
			$variantKey = preg_replace('/([A-Z]+)/', '_$1', $variant);
			$variantKey = Market\Data\TextString::toUpper($variantKey);

			$result[] = [
				'ID' => $variant,
				'VALUE' => static::getLang('TRADING_SERVICE_OPTION_BUYER_PROFILE_RULE_' . $variantKey, null, $variant),
			];
		}

		return $result;
	}
}