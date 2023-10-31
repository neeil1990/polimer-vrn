<?php

namespace Yandex\Market\Trading\Service\Common;

use Yandex\Market;
use Bitrix\Main;

class TaxSystem
{
	use Market\Reference\Concerns\HasLang;

	const TYPE_ECHN = 'ECHN';
	const TYPE_ENVD = 'ENVD';
	const TYPE_OSN = 'OSN';
	const TYPE_PSN = 'PSN';
	const TYPE_USN = 'USN';
	const TYPE_USN_MINUS_COST = 'USN_MINUS_COST';

	protected $provider;

	protected static function includeMessages()
	{
		Main\Localization\Loc::loadMessages(__FILE__);
	}

	public function __construct(Provider $provider)
	{
		$this->provider = $provider;
	}

	public function isValidType($type)
	{
		return in_array($type, $this->getTypes(), true);
	}

	public function getDefaultType()
	{
		return static::TYPE_OSN;
	}

	public function getTypes()
	{
		return [
			static::TYPE_ECHN,
			static::TYPE_ENVD,
			static::TYPE_OSN,
			static::TYPE_PSN,
			static::TYPE_USN,
			static::TYPE_USN_MINUS_COST,
		];
	}

	public function getTypeTitle($type)
	{
		return static::getLang('TRADING_SERVICE_COMMON_TAX_SYSTEM_' . $type, null, $type);
	}

	public function getTypeEnum()
	{
		$typeList = $this->getTypes();
		$result = [];

		foreach ($typeList as $type)
		{
			$result[] = [
				'ID' => $type,
				'VALUE' => $this->getTypeTitle($type),
			];
		}

		return $result;
	}
}