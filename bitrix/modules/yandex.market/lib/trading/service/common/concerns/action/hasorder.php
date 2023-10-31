<?php

namespace Yandex\Market\Trading\Service\Common\Concerns\Action;

use Yandex\Market;
use Yandex\Market\Trading\Entity as TradingEntity;
use Yandex\Market\Trading\Service as TradingService;

/**
 * trait HasOrder
 * @property TradingService\Common\Provider $provider
 * @property TradingEntity\Reference\Environment $environment
 * @property TradingService\Common\Action\SendRequest $request
 */
trait HasOrder
{
	/** @var TradingEntity\Reference\Order */
	protected $order;
	/** @var Market\Api\Model\Order */
	protected $externalOrder;

	protected function getOrder()
	{
		if ($this->order === null)
		{
			$this->order = $this->loadOrder();
		}

		return $this->order;
	}

	protected function loadOrder()
	{
		$orderId = $this->request->getInternalId();
		$orderRegistry = $this->environment->getOrderRegistry();

		return $orderRegistry->loadOrder($orderId);
	}

	protected function updateOrder(TradingEntity\Reference\Order $order = null)
	{
		if ($order === null) { $order = $this->getOrder(); }

		$updateResult = $order->update();

		Market\Result\Facade::handleException($updateResult);
	}

	protected function getExternalOrder()
	{
		if ($this->externalOrder === null)
		{
			$this->externalOrder = $this->loadExternalOrder();
		}

		return $this->externalOrder;
	}

	protected function loadExternalOrder()
	{
		$primary = $this->request->getOrderId();

		if (Market\Trading\State\HitCache::has('order', $primary))
		{
			$fields = Market\Trading\State\HitCache::get('order', $primary);
			$orderClassName = $this->provider->getModelFactory()->getOrderClassName();

			$result = $orderClassName::initialize($fields);
		}
		else if (Market\Trading\State\SessionCache::has('order', $primary))
		{
			$fields = Market\Trading\State\SessionCache::get('order', $primary);
			$orderClassName = $this->provider->getModelFactory()->getOrderClassName();

			$result = $orderClassName::initialize($fields);
		}
		else
		{
			$options = $this->provider->getOptions();
			$facadeClassName = $this->provider->getModelFactory()->getOrderFacadeClassName();

			$result = $facadeClassName::load($options, $primary);
		}

		return $result;
	}
}