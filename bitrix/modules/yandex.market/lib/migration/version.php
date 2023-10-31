<?php

namespace Yandex\Market\Migration;

use Bitrix\Main;
use Yandex\Market;

class Version
{
	public static function check($method)
	{
		$checkedVersion = static::getChecked($method);
		$currentVersion = static::getCurrent();

		return CheckVersion($checkedVersion, $currentVersion);
	}

	public static function update($method)
	{
		$currentVersion = static::getCurrent();

		static::setChecked($method, $currentVersion);
	}

	protected static function getCurrent()
	{
		$moduleName = Market\Config::getModuleName();

		return Main\ModuleManager::getVersion($moduleName);
	}

	protected static function getChecked($method)
	{
		return Market\Config::getOption('migration_last_version_' . $method, '1.0.0');
	}

	protected static function setChecked($method, $version)
	{
		Market\Config::setOption('migration_last_version_' . $method, $version);
	}
}