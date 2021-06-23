<?php

namespace Yandex\Market\Ui\Admin;

class Path
{
	const MODULE_PATH_PREFIX = 'yamarket_';

	public static function getPageUrl($scriptName, $query = null)
	{
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

		return static::getPageUrl($fullScriptName, $query);
	}
}