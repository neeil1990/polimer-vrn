<?php

namespace Yandex\Market\Trading\State\Internals;

use Yandex\Market;
use Bitrix\Main;

class StatusProxy
{
	protected static $stored = [];
	protected static $changed = [];

	public static function get($service, $orderId)
	{
		$key = $service . ':' . $orderId;

		return isset(static::$changed[$key])
			? static::$changed[$key]
			: static::getStored($service, $orderId);
	}

	public static function set($service, $orderId, $value)
	{
		static::$changed[$service . ':' . $orderId] = (string)$value;
	}

	public static function commit($service, $orderId)
	{
		$key = $service . ':' . $orderId;

		if (!isset(static::$changed[$key])) { return; }

		$value = static::$changed[$key];

		static::store($service, $orderId, $value);

		static::$stored[$key] = $value;
		unset(static::$changed[$key]);
	}

	protected static function getStored($service, $orderId)
	{
		$key = $service . ':' . $orderId;

		if (!array_key_exists($key, static::$stored))
		{
			static::$stored[$key] = static::fetch($service, $orderId);
		}

		return static::$stored[$key];
	}

	protected static function store($service, $orderId, $value)
	{
		$stored = static::getStored($service, $orderId);
		$value = (string)$value;

		if ($stored === $value) { return; }

		$primary = [
			'SERVICE' => $service,
			'ENTITY_ID' => $orderId,
		];
		$fields = [
			'VALUE' => $value,
			'TIMESTAMP_X' => new Main\Type\DateTime(),
		];

		$writeResult = ($stored === null)
			? StatusTable::add($primary + $fields)
			: StatusTable::update($primary, $fields);

		Market\Result\Facade::handleException($writeResult);
	}

	protected static function fetch($service, $orderId)
	{
		$result = null;

		$query = StatusTable::getList([
			'filter' => [
				'=SERVICE' => $service,
				'=ENTITY_ID' => $orderId,
			],
			'select' => [ 'VALUE' ],
		]);

		if ($row = $query->fetch())
		{
			$result = (string)$row['VALUE'];
		}

		return $result;
	}
}