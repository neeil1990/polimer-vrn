<?php

namespace Yandex\Market\Export\Run;

use Yandex\Market;

class Admin
{
	public static function progress($setupId)
	{
		Market\Config::setOption('setup_run_mtime_' . (int)$setupId, time());
	}

	public static function release($setupId)
	{
		Market\Config::removeOption('setup_run_mtime_' . (int)$setupId);
	}

	public static function getProgressTime($setupId)
	{
		$option = (int)Market\Config::getOption('setup_run_mtime_' . (int)$setupId);

		return ($option > 0 ? $option : null);
	}

	public static function hasProgress($setupId)
	{
		$progressTime = static::getProgressTime($setupId);

		return ($progressTime !== null);
	}

	public static function isProgressExpired($setupId)
	{
		$result = false;
		$progressTime = static::getProgressTime($setupId);

		if ($progressTime !== null && time() - $progressTime > static::getExpirePeriod())
		{
			$result = true;
		}

		return $result;
	}

	public static function getTimeLimit()
	{
		return (int)Market\Config::getOption('setup_run_time_limit', 30);
	}

	public static function setTimeLimit($seconds)
	{
		$seconds = (int)$seconds;

		if ($seconds > 0)
		{
			Market\Config::setOption('setup_run_time_limit', $seconds);
		}
	}

	public static function getTimeSleep()
	{
		return (int)Market\Config::getOption('setup_run_time_sleep', 3);
	}

	public static function setTimeSleep($seconds)
	{
		$seconds = (int)$seconds;

		if ($seconds > 0)
		{
			Market\Config::setOption('setup_run_time_sleep', $seconds);
		}
	}

	protected static function getExpirePeriod()
	{
		return static::getTimeLimit() * 2 + static::getTimeSleep();
	}
}