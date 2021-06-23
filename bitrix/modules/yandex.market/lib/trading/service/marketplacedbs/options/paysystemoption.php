<?php

namespace Yandex\Market\Trading\Service\MarketplaceDbs\Options;

use Bitrix\Main;
use Yandex\Market;
use Yandex\Market\Trading\Entity as TradingEntity;
use Yandex\Market\Trading\Service as TradingService;

class PaySystemOption extends TradingService\Reference\Options\Fieldset
{
	use Market\Reference\Concerns\HasLang;

	/** @var TradingService\MarketplaceDbs\Provider $provider */
	protected $provider;

	protected static function includeMessages()
	{
		Main\Localization\Loc::loadMessages(__FILE__);
	}

	/** @return int */
	public function getPaySystemId()
	{
		return (int)$this->getRequiredValue('ID');
	}

	/** @return string */
	public function getMethod()
	{
		return (string)$this->getRequiredValue('METHOD');
	}

	public function getFieldDescription(TradingEntity\Reference\Environment $environment, $siteId)
	{
		return parent::getFieldDescription($environment, $siteId) + [
			'SETTINGS' => [
				'SUMMARY' => '&laquo;#ID#&raquo; (#METHOD#)',
				'LAYOUT' => 'summary',
			]
		];
	}

	public function getFields(TradingEntity\Reference\Environment $environment, $siteId)
	{
		try
		{
			$result = [
				'ID' => [
					'TYPE' => 'enumeration',
					'MANDATORY' => 'Y',
					'NAME' => static::getLang('TRADING_SERVICE_MARKETPLACE_OPTIONS_PAY_SYSTEM_OPTION_ID'),
					'VALUES' => $environment->getPaySystem()->getEnum($siteId),
				],
				'METHOD' => [
					'TYPE' => 'enumeration',
					'MANDATORY' => 'Y',
					'NAME' => static::getLang('TRADING_SERVICE_MARKETPLACE_OPTIONS_PAY_SYSTEM_OPTION_METHOD'),
					'VALUES' => $this->provider->getPaySystem()->getMethodEnum(),
				],
			];
		}
		catch (Market\Exceptions\NotImplemented $exception)
		{
			$result = [];
		}

		return $result;
	}
}
