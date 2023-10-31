<?php

namespace Yandex\Market\Trading\Entity\Reference;

use Yandex\Market;
use Yandex\Market\Trading\Service as TradingService;

interface OutletInvertible
{
	public function isMatchService(Market\Api\Delivery\Services\Model\DeliveryService $deliveryService);

	public function searchByAddress(Order $order, Market\Api\Model\Region $region, TradingService\MarketplaceDbs\Model\Order\Delivery\Address $address);

	public function searchByCode(Order $order, Market\Api\Model\Region $region, $code);
}