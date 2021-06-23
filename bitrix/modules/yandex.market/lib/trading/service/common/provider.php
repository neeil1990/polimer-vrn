<?php

namespace Yandex\Market\Trading\Service\Common;

use Yandex\Market;
use Bitrix\Main;
use Yandex\Market\Trading\Service as TradingService;

abstract class Provider extends TradingService\Reference\Provider
{
	protected $status;
	protected $taxSystem;

	public function wakeup()
	{
		$this->wakeupLogger();
	}

	protected function createLogger()
	{
		return new Market\Logger\Trading\Logger();
	}

	protected function wakeupLogger()
	{
		$logger = $this->getLogger();
		$options = $this->getOptions();

		$logger->setLevel($options->getLogLevel());
		$logger->setEntityParent($options->getSetupId());
	}

	public function getTaxSystem()
	{
		if ($this->taxSystem === null)
		{
			$this->taxSystem = $this->createTaxSystem();
		}

		return $this->taxSystem;
	}

	protected function createTaxSystem()
	{
		return new TaxSystem($this);
	}
}