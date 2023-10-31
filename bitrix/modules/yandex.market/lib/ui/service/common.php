<?php

namespace Yandex\Market\Ui\Service;

use Yandex\Market;
use Bitrix\Main;

class Common extends AbstractService
{
	use Market\Reference\Concerns\HasLang;

	protected $specialServices;

	protected static function includeMessages()
	{
		Main\Localization\Loc::loadMessages(__FILE__);
	}

	public function getTitle($version = '')
	{
		$suffix = $version !== '' ? '_' . $version : '';

		return static::getLang('UI_SERVICE_COMMON_TITLE' . $suffix);
	}

	public function isInverted()
	{
		return true;
	}

	public function getExportServices()
	{
		$specialExportServices = $this->callSpecialServices('getExportServices');

		return array_unique(array_merge(...$specialExportServices));
	}

	public function getTradingServices()
	{
		$specialTradingServices = $this->callSpecialServices('getTradingServices');

		return array_unique(array_merge(...$specialTradingServices));
	}

	protected function getSpecialServices()
	{
		if ($this->specialServices === null)
		{
			$this->specialServices = $this->loadSpecialServices();
		}

		return $this->specialServices;
	}

	protected function loadSpecialServices()
	{
		$result = [];

		foreach (Manager::getTypes() as $type)
		{
			$result[] = Manager::getInstance($type);
		}

		return $result;
	}

	protected function callSpecialServices($method, array $args = null)
	{
		$result = [];

		foreach ($this->getSpecialServices() as $specialService)
		{
			if ($args !== null)
			{
				$callResult = $specialService->{$method}(...$args);
			}
			else
			{
				$callResult = $specialService->{$method}();
			}

			$result[] = $callResult;
		}

		return $result;
	}
}