<?php

namespace Yandex\Market\Trading\Service\MarketplaceDbs;

use Yandex\Market;
use Bitrix\Main;
use Yandex\Market\Trading\Service as TradingService;

/**
 * @method Options getOptions()
 * @method Status getStatus()
 * @method Delivery getDelivery()
 */
class Provider extends TradingService\Marketplace\Provider
	implements
		TradingService\Reference\HasCancelReason,
		TradingService\Reference\HasCancellationAccept,
		TradingService\Reference\HasItemsChangeReason
{
	protected $cancelReason;
	protected $cancellationAccept;
	protected $itemsChangeReason;

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

	protected function createInstaller()
	{
		return new Installer($this);
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

	public function getCancellationAccept()
	{
		if ($this->cancellationAccept === null)
		{
			$this->cancellationAccept = $this->createCancellationAccept();
		}

		return $this->cancellationAccept;
	}

	protected function createCancellationAccept()
	{
		return new CancellationAccept($this);
	}

	public function getItemsChangeReason()
	{
		if ($this->itemsChangeReason === null)
		{
			$this->itemsChangeReason = $this->createItemsChangeReason();
		}

		return $this->itemsChangeReason;
	}

	protected function createItemsChangeReason()
	{
		return new ItemsChangeReason($this);
	}

	protected function createFeature()
	{
		return new Feature($this);
	}
}