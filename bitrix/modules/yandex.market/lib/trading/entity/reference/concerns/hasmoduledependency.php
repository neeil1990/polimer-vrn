<?php

namespace Yandex\Market\Trading\Entity\Reference\Concerns;

use Bitrix\Main;

trait HasModuleDependency
{
	public function canLoad()
	{
		$result = true;

		foreach ($this->requiredModules() as $module => $version)
		{
			if (is_numeric($module))
			{
				$module = $version;
				$version = false;
			}

			if (!Main\ModuleManager::isModuleInstalled($module))
			{
				$result = false;
				break;
			}

			if ($version !== false && !CheckVersion(Main\ModuleManager::getVersion($module), $version))
			{
				$result = false;
				break;
			}
		}

		return $result;
	}

	public function load()
	{
		foreach ($this->requiredModules() as $module => $version)
		{
			if (is_numeric($module))
			{
				$module = $version;
			}

			if (!Main\Loader::includeModule($module))
			{
				throw new Main\SystemException(sprintf('module %s required', $module));
			}
		}
	}

	protected function requiredModules()
	{
		return [];
	}
}