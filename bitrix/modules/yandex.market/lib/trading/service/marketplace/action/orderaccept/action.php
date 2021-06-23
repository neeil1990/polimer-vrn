<?php

namespace Yandex\Market\Trading\Service\Marketplace\Action\OrderAccept;

use Yandex\Market;
use Bitrix\Main;
use Yandex\Market\Trading\Entity as TradingEntity;
use Yandex\Market\Trading\Service as TradingService;

/** @property TradingService\Marketplace\Provider $provider */
class Action extends TradingService\Common\Action\OrderAccept\Action
{
	/** @var Request */
	protected $request;

	protected function createRequest(Main\HttpRequest $request, Main\Server $server)
	{
		return new Request($request, $server);
	}

	protected function fillDelivery()
	{
		$deliveryId = $this->provider->getOptions()->getDeliveryId();

		if ($deliveryId !== '')
		{
			$this->order->createShipment($deliveryId);
		}
	}

	protected function fillPaySystem()
	{
		$this->fillPaySystemSubsidy();
		$this->fillPaySystemCommon();
	}

	protected function fillPaySystemSubsidy()
	{
		$options = $this->provider->getOptions();
		$subsidySystemId = $options->getSubsidyPaySystemId();

		if ($subsidySystemId !== '' && $options->includeBasketSubsidy())
		{
			$items = $this->request->getOrder()->getItems();
			$subsidySum = $items->getSubsidySum();

			if ($subsidySum > 0)
			{
				$this->order->createPayment($subsidySystemId, $subsidySum, [
					'SUBSIDY' => true,
					'ORDER_ID' => $this->request->getOrder()->getId(),
				]);
			}
		}
	}

	protected function fillPaySystemCommon()
	{
		$paySystemId = $this->resolvePaySystem();

		if ($paySystemId !== '')
		{
			$this->order->createPayment($paySystemId);
		}
	}

	protected function resolvePaySystem()
	{
		return $this->provider->getOptions()->getPaySystemId();
	}

	protected function getItemPrice(Market\Api\Model\Order\Item $item)
	{
		if ($this->provider->getOptions()->includeBasketSubsidy())
		{
			$result = $item->getPrice() + $item->getSubsidy();
		}
		else
		{
			$result = $item->getPrice();
		}

		return $result;
	}
}