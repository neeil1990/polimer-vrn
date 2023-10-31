<?php

namespace Yandex\Market\Ui\Service;

use Yandex\Market;
use Bitrix\Main;

abstract class AbstractService
{
	protected $code;

	public function __construct($code)
	{
		$this->code = $code;
	}

	abstract public function getTitle($version = '');

	public function getCode()
	{
		return $this->code;
	}

	public function isInverted()
	{
		return false;
	}

	public function getServices($behavior)
	{
		switch ($behavior)
		{
			case Manager::BEHAVIOR_TRADING:
				$result = $this->getTradingServices();
			break;

			case Manager::BEHAVIOR_EXPORT:
			default:
				$result = $this->getExportServices();
			break;
		}

		return $result;
	}

	abstract public function getExportServices();

	/**
	 * @deprecated
	 * @return array
	 */
	public function getExportSetupDisabledFields()
	{
		return [];
	}

	abstract public function getTradingServices();
}