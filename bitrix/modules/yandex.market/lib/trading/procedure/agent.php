<?php

namespace Yandex\Market\Trading\Procedure;

use Yandex\Market;
use Bitrix\Main;

class Agent extends Market\Reference\Agent\Base
{
	protected static $previousUser = null;
	protected static $isPlaceholderUserCreated = false;

	public static function repeat()
	{
		$repeater = new Repeater();

		static::processRepeatQueue($repeater);

		return static::modifyRepeatPeriod($repeater);
	}

	protected static function processRepeatQueue(Repeater $repeater)
	{
		static::createPlaceholderUser();
		$repeater->processQueue();
		static::releasePlaceholderUser();
	}

	protected static function modifyRepeatPeriod(Repeater $repeater)
	{
		global $pPERIOD;

		$nearestInterval = $repeater->getNearestQueueInterval();
		$result = false;

		if ($nearestInterval !== null)
		{
			$result = true;
			$pPERIOD = $nearestInterval;
		}

		return $result;
	}

	protected static function createPlaceholderUser()
	{
		global $USER;

		if (!($USER instanceof \CUser))
		{
			static::$isPlaceholderUserCreated = true;
			static::$previousUser = $USER;

			$USER = new \CUser();
		}
	}

	protected static function releasePlaceholderUser()
	{
		global $USER;

		if (static::$isPlaceholderUserCreated)
		{
			if (static::$previousUser !== null)
			{
				$USER = static::$previousUser;
			}
			else
			{
				unset($USER);
			}
		}
	}
}