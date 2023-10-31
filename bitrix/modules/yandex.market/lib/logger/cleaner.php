<?php

namespace Yandex\Market\Logger;

use Yandex\Market;
use Bitrix\Main;

class Cleaner extends Market\Reference\Agent\Regular
{
	public static function getDefaultParams()
	{
		return [
			'interval' => 86400,
		];
	}

	public static function run()
	{
		$tables = static::getTables();
		$days = static::getExpireDays();

		if ($days > 0)
		{
			$date = static::buildExpireDate($days);

			foreach ($tables as $table)
			{
				static::cleanTable($table, $date);
			}
		}
	}

	/**
	 * @return Market\Reference\Storage\Table[]
	 */
	protected static function getTables()
	{
		return [
			Trading\Table::class,
		];
	}

	/**
	 * @param Market\Reference\Storage\Table $dataClass
	 * @param Main\Type\DateTime $dateTime
	 */
	protected static function cleanTable($dataClass, Main\Type\DateTime $dateTime)
	{
		$dataClass::deleteBatch([
			'filter' => [ '<=TIMESTAMP_X' => $dateTime ],
		]);
	}

	/**
	 * @return int
	 */
	protected static function getExpireDays()
	{
		return (int)Market\Config::getOption('log_expire_days', 30);
	}

	/**
	 * @param int $days
	 *
	 * @return \Bitrix\Main\Type\DateTime
	 * @throws \Bitrix\Main\ObjectException
	 */
	protected static function buildExpireDate($days)
	{
		$result = new Main\Type\DateTime();
		$result->add('-P' . (int)$days . 'D');

		return $result;
	}
}