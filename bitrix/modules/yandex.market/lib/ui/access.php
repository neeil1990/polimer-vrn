<?php

namespace Yandex\Market\Ui;

use Yandex\Market;

class Access
{
	const RIGHTS_PROCESS_TRADING = 'PT';
	const RIGHTS_PROCESS_EXPORT = 'PE';
	const RIGHTS_READ = 'R';
	const RIGHTS_WRITE = 'W';

	public static function isProcessTradingAllowed()
	{
		return static::hasRights(static::RIGHTS_PROCESS_TRADING);
	}

	public static function isProcessExportAllowed()
	{
		return static::hasRights(static::RIGHTS_PROCESS_EXPORT);
	}

	public static function isReadAllowed()
	{
		return static::hasRights(static::RIGHTS_READ);
	}

	public static function isWriteAllowed()
	{
		return static::hasRights(static::RIGHTS_WRITE);
	}

	public static function hasRights($level)
	{
		$rights = static::getRights();

		if ($rights[0] < $level[0])
		{
			$result = false;
		}
		else if ($rights[0] > $level[0])
		{
			$result = true;
		}
		else
		{
			$result = ($rights === $level);
		}

		return $result;
	}

	protected static function getRights()
	{
		$moduleId = Market\Config::getModuleName();

		return \CMain::GetUserRight($moduleId);
	}
}