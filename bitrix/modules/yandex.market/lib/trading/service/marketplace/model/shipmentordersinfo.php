<?php

namespace Yandex\Market\Trading\Service\Marketplace\Model;

use Yandex\Market;

class ShipmentOrdersInfo extends Market\Api\Reference\Model
{
	public function getOrderIdsWithLabels()
	{
		return (array)$this->getField('orderIdsWithLabels');
	}

	public function getOrderIdsWithoutLabels()
	{
		return (array)$this->getField('orderIdsWithoutLabels');
	}
}