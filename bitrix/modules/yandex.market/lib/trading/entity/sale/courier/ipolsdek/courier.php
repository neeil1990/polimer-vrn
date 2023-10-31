<?php

namespace Yandex\Market\Trading\Entity\Sale\Courier\IpolSdek;

use Bitrix\Sale;
use Yandex\Market;
use Yandex\Market\Trading\Entity as TradingEntity;

/** @noinspection PhpUnused */
class Courier extends TradingEntity\Sale\Courier
	implements TradingEntity\Reference\CourierInvertible
{
	const MARKET_SERVICE_ID = 51;

	protected function requiredModules()
	{
		return [
			'ipol.sdek' => '3.5.0',
		];
	}

	public function isMatch($deliveryId)
	{
		$deliveryService = $this->environment->getDelivery()->getDeliveryService($deliveryId);

		if (!($deliveryService instanceof Sale\Delivery\Services\AutomaticProfile)) { return false; }

		return $deliveryService->getCode() === 'sdek:courier';
	}

	public function isMatchService(Market\Api\Delivery\Services\Model\DeliveryService $deliveryService)
	{
		return $deliveryService->getId() === static::MARKET_SERVICE_ID;
	}
}