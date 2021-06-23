<?php

namespace Yandex\Market\Api;

use Bitrix\Main;
use Yandex\Market;

class Marker
{
	public static function getHeader()
	{
		$moduleName = Market\Config::getModuleName();
		$moduleVersion = Main\ModuleManager::getVersion($moduleName);

		return [
			'X-Module-Version',
			'market-bitrix-version-' . $moduleVersion
		];
	}
}