<?php

namespace Yandex\Market\Trading\Entity\Reference;

use Yandex\Market;

interface CourierInvertible
{
	public function isMatchService(Market\Api\Delivery\Services\Model\DeliveryService $deliveryService);
}