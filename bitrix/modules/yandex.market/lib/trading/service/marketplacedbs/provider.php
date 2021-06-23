<?php

namespace Yandex\Market\Trading\Service\MarketplaceDbs;

use Yandex\Market;
use Bitrix\Main;
use Yandex\Market\Trading\Service as TradingService;

/** @method Options getOptions() */
class Provider extends TradingService\Marketplace\Provider
{
	protected $delivery;
	protected $cancelReason;

	public function getBehaviorCode()
	{
		return 'dbs';
	}

	protected function createInfo()
	{
		return new Info($this);
	}

	protected function createRouter()
	{
		return new Router($this);
	}

	protected function createOptions()
	{
		return new Options($this);
	}

	protected function createStatus()
	{
		return new Status($this);
	}

	protected function createPrinter()
	{
		return new Printer($this);
	}

	protected function createModelFactory()
	{
		return new ModelFactory($this);
	}

	public function getDelivery()
	{
		if ($this->delivery === null)
		{
			$this->delivery = $this->createDelivery();
		}

		return $this->delivery;
	}

	protected function createDelivery()
	{
		return new Delivery($this);
	}

	public function getCancelReason()
	{
		if ($this->cancelReason === null)
		{
			$this->cancelReason = $this->createCancelReason();
		}

		return $this->cancelReason;
	}

	protected function createCancelReason()
	{
		return new CancelReason($this);
	}

	protected function createFeature()
	{
		return new Feature($this);
	}
}