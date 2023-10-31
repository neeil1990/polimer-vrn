<?php

namespace Yandex\Market\Migration;

use Bitrix\Main;
use Yandex\Market;

class File
{
	public static function canRestore($exception)
	{
		return false;
	}

	public static function check()
	{
		return false;
	}

	public static function reset()
	{
		$moduleName = Market\Config::getModuleName();
		$installer = \CModule::CreateModuleObject($moduleName);

		if (!$installer) { return; }

		$installer->InstallFiles();
	}
}