<?php

namespace Yandex\Market\Data\Holiday;

use Bitrix\Main;
use Yandex\Market;

class Registry
{
	const NATIONAL = 'national';
	const PRODUCTION = 'production';
	const BLANK = 'blank';
	const MANUAL = 'manual';

	/** @var array<string, CalendarInterface>*/
	protected static $userMap;

	public static function types()
	{
		return array_merge(
			static::userTypes(),
			static::systemTypes()
		);
	}

	protected static function systemTypes()
	{
		return [
			static::PRODUCTION,
			static::NATIONAL,
			static::BLANK,
			static::MANUAL,
		];
	}

	protected static function userTypes()
	{
		$map = static::loadUserMap();

		return array_keys($map);
	}

	public static function instance($type)
	{
		if (in_array($type, static::systemTypes(), true))
		{
			$className = static::systemName($type);
		}
		else
		{
			$userMap = static::userMap();

			if (!isset($userMap[$type]))
			{
				throw new Main\SystemException(sprintf('unknown %s holiday calendar', $type));
			}

			$className = $userMap[$type];
		}

		Market\Reference\Assert::classExists($className);
		Market\Reference\Assert::isSubclassOf($className, CalendarInterface::class);

		return new $className();
	}

	protected static function systemName($type)
	{
		return __NAMESPACE__ . '\\' . ucfirst($type);
	}

	protected static function userMap()
	{
		if (static::$userMap === null)
		{
			static::$userMap = static::loadUserMap();
		}

		return static::$userMap;
	}

	protected static function loadUserMap()
	{
		$moduleName = Market\Config::getModuleName();
		$eventName = 'onHolidayCalendar';
		$result = [];

		$event = new Main\Event($moduleName, $eventName);
		$event->send();

		foreach ($event->getResults() as $eventResult)
		{
			if ($eventResult->getType() !== Main\EventResult::SUCCESS) { continue; }

			$parameters = $eventResult->getParameters();

			Market\Reference\Assert::isArray($parameters, 'eventResult->getParameters()');

			foreach ($parameters as $code => $calendar)
			{
				Market\Reference\Assert::classExists($calendar);
				Market\Reference\Assert::isSubclassOf($calendar, CalendarInterface::class);

				$result[$code] = $calendar;
			}
		}

		return $result;
	}
}