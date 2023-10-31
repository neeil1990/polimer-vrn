<?php

namespace Yandex\Market\Api\Delivery\Services;

use Yandex\Market;

class Response extends Market\Api\Reference\Response
{
	/** @return Model\DeliveryServiceCollection */
	public function getDeliveryServices()
	{
		return $this->getRequiredCollection('result.deliveryService');
	}

	protected function getChildCollectionReference()
	{
		return [
			'result.deliveryService' => Model\DeliveryServiceCollection::class,
		];
	}
}