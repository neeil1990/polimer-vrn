<?php

namespace Yandex\Market\Trading\Service\Common\Concerns\Options;

use Yandex\Market;
use Bitrix\Main;
use Yandex\Market\Trading\Entity as TradingEntity;
use Yandex\Market\Trading\Service as TradingService;

class UserRegistration
{
	use Market\Reference\Concerns\HasLang;

	protected static function includeMessages()
	{
		Main\Localization\Loc::loadMessages(__FILE__);
	}

	/** @deprecated */
	public static function getDefault()
	{
		return UserRegistrationInterface::USER_RULE_MATCH_ANY;
	}

	public static function getFields(TradingEntity\Reference\Environment $environment, $siteId)
	{
		return [
			'ORDER_USER_RULE' => [
				'TYPE' => 'enumeration',
				'NAME' => static::getLang('TRADING_SERVICE_OPTION_ORDER_USER_RULE'),
				'VALUES' => static::getOrderUserRuleEnum(),
				'HIDDEN' => Market\Config::isExpertMode() ? 'N' : 'Y',
				'SORT' => 3400,
				'SETTINGS' => [
					'ALLOW_NO_VALUE' => 'N',
					'STYLE' => 'max-width: 450px;'
				],
			],
		];
	}

	protected static function getOrderUserRuleEnum()
	{
		$variants = [
			UserRegistrationInterface::USER_RULE_MATCH_ANY,
			UserRegistrationInterface::USER_RULE_MATCH_PHONE,
			UserRegistrationInterface::USER_RULE_MATCH_EMAIL,
			UserRegistrationInterface::USER_RULE_MATCH_NAME,
			UserRegistrationInterface::USER_RULE_MATCH_ID,
			UserRegistrationInterface::USER_RULE_ANONYMOUS,
		];
		$result = [];

		foreach ($variants as $variant)
		{
			$variantKey = Market\Data\TextString::toUpper($variant);

			$result[] = [
				'ID' => $variant,
				'VALUE' => static::getLang('TRADING_SERVICE_OPTION_ORDER_USER_RULE_' . $variantKey, null, $variant),
			];
		}

		return $result;
	}
}