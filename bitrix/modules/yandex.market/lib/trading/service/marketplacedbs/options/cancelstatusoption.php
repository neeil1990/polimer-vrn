<?php

namespace Yandex\Market\Trading\Service\MarketplaceDbs\Options;

use Bitrix\Main;
use Yandex\Market;
use Yandex\Market\Trading\Entity as TradingEntity;
use Yandex\Market\Trading\Service as TradingService;

class CancelStatusOption extends TradingService\Reference\Options\Fieldset
{
	use Market\Reference\Concerns\HasLang;

	/** @var TradingService\MarketplaceDbs\Provider $provider */
	protected $provider;

	protected static function includeMessages()
	{
		Main\Localization\Loc::loadMessages(__FILE__);
	}

	/** @return string */
	public function getStatus()
	{
		return (string)$this->getRequiredValue('STATUS');
	}

	/** @return string */
	public function getCancelReason()
	{
		$value = (string)$this->getValue('CANCEL_REASON');

		return $value !== '' ? $value : null;
	}

	public function getFields(TradingEntity\Reference\Environment $environment, $siteId)
	{
		try
		{
			$environmentStatus = $environment->getStatus();
			$serviceCancelReason = $this->provider->getCancelReason();

			$result = [
				'STATUS' => [
					'TYPE' => 'enumeration',
					'MANDATORY' => 'Y',
					'NAME' => static::getLang('TRADING_SERVICE_MARKETPLACE_OPTIONS_CANCEL_STATUS_OPTION_STATUS_OUT'),
					'VALUES' => $environmentStatus->getEnum($environmentStatus->getVariants()),
					'SETTINGS' => [
						'STYLE' => 'max-width: 300px;',
					],
				],
				'CANCEL_REASON' => [
					'TYPE' => 'enumeration',
					'NAME' => static::getLang('TRADING_SERVICE_MARKETPLACE_OPTIONS_CANCEL_STATUS_OPTION_CANCEL_REASON'),
					'VALUES' => array_map(static function($reason) use ($serviceCancelReason) {
						return [
							'ID' => $reason,
							'VALUE' => $serviceCancelReason->getTitle($reason),
						];
					}, $serviceCancelReason->getVariants()),
					'SETTINGS' => [
						'STYLE' => 'max-width: 300px;',
						'CAPTION_NO_VALUE' => static::getLang('TRADING_SERVICE_MARKETPLACE_OPTIONS_CANCEL_STATUS_OPTION_CANCEL_REASON_NO_VALUE'),
						'DEFAULT_GROUP' => static::getLang('TRADING_SERVICE_MARKETPLACE_OPTIONS_CANCEL_STATUS_OPTION_CANCEL_DEFAULT_GROUP'),
					],
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
