<?php

namespace Yandex\Market\Trading\State\Internals;

use Yandex\Market;
use Bitrix\Main;

class DataCleaner extends Market\Reference\Agent\Regular
{
	public static function getDefaultParams()
	{
		return [
			'interval' => 86400,
		];
	}

	public static function run()
	{
		$types = [
			'data' => DataTable::class,
			'entity' => EntityTable::class,
			'status' => StatusTable::class,
			'push' => PushTable::class,
		];

		/** @var Market\Reference\Storage\Table $dataClass */
		foreach ($types as $type => $dataClass)
		{
			$days = static::getExpireDays($type);

			if ($days <= 0) { return; }

			$date = static::buildExpireDate($days);

			$dataClass::deleteBatch([
				'filter' => [ '<=TIMESTAMP_X' => $date ],
			]);
		}
	}

	public static function getExpireDays($type)
	{
		$option = sprintf('trading_%s_expire_days', $type);

		return (int)Market\Config::getOption($option, 30);
	}

	protected static function buildExpireDate($days)
	{
		$result = new Main\Type\DateTime();
		$result->add('-P' . (int)$days . 'D');

		return $result;
	}
}