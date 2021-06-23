<?php

namespace Yandex\Market\Trading\Service\Turbo;

use Yandex\Market;
use Bitrix\Main;

class PaySystem
{
	use Market\Reference\Concerns\HasLang;

	const TYPE_PREPAID = 'PREPAID';
	const TYPE_POSTPAID = 'POSTPAID';

	protected $provider;

	protected static function includeMessages()
	{
		Main\Localization\Loc::loadMessages(__FILE__);
	}

	public function __construct(Provider $provider)
	{
		$this->provider = $provider;
	}

	public function getTypeVariants()
	{
		return [
			static::TYPE_PREPAID,
			static::TYPE_POSTPAID,
		];
	}

	public function getTypeTitle($type, $version = '')
	{
		$typeKey = Market\Data\TextString::toUpper($type);
		$versionSuffix = ($version !== '' ? '_' . $version : '');

		return static::getLang('TRADING_SERVICE_TURBO_PAY_SYSTEM_TYPE_' . $typeKey . $versionSuffix, null, $type);
	}

	public function isPrepaid($type)
	{
		return $type === static::TYPE_PREPAID;
	}
}