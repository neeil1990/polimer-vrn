<?php

namespace Yandex\Market\Ui\Admin;

use Yandex\Market\Config;
use Yandex\Market\Data\TextString;

class Path
{
	const MODULE_PATH_PREFIX = 'yamarket_';

	public static function getPageUrl($scriptName, $query = null)
	{
		$scriptName = TextString::toLower($scriptName);
		$path = BX_ROOT . '/admin/' . $scriptName . '.php';

		if ($query !== null)
		{
			$path .= '?' . http_build_query($query);
		}

		return $path;
	}

	public static function getModuleUrl($scriptName, $query = null)
	{
		$fullScriptName = static::MODULE_PATH_PREFIX . $scriptName;

		if (!isset($query['lang']) && defined('LANGUAGE_ID'))
		{
			if ($query === null) { $query = []; }

			$query = ['lang' => LANGUAGE_ID] + $query;
		}

		return static::getPageUrl($fullScriptName, $query);
	}

	public static function getToolsUrl($scriptPath, $query = null)
	{
		$scriptPath = TextString::toLower($scriptPath);
		$path = BX_ROOT . '/tools/' . Config::getModuleName() . '/' . $scriptPath . '.php';

		if ($query !== null)
		{
			$path .= '?' . http_build_query($query);
		}

		return $path;
	}
}