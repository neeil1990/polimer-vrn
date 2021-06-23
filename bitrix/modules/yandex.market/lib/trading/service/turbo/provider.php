<?php

namespace Yandex\Market\Trading\Service\Turbo;

use Yandex\Market;
use Bitrix\Main;
use Yandex\Market\Trading\Service as TradingService;

/** @method Options getOptions() */
class Provider extends TradingService\Common\Provider
{
	protected $status;
	protected $paySystem;

	protected function createRouter()
	{
		return new Router($this);
	}

	protected function createInstaller()
	{
		return new Installer($this);
	}

	protected function createOptions()
	{
		return new Options($this);
	}

	protected function createInfo()
	{
		return new Info($this);
	}

	public function getStatus()
	{
		if ($this->status === null)
		{
			$this->status = $this->createStatus();
		}

		return $this->status;
	}

	protected function createStatus()
	{
		return new Status($this);
	}

	public function getPaySystem()
	{
		if ($this->paySystem === null)
		{
			$this->paySystem = $this->createPaySystem();
		}

		return $this->paySystem;
	}

	protected function createPaySystem()
	{
		return new PaySystem($this);
	}
}