<?php

namespace Yandex\Market\Trading\Service\Marketplace\Api\Shipment;

use Yandex\Market;
use Yandex\Market\Trading\Service as TradingService;

class Response extends Market\Api\Reference\ResponseWithResult
{
	use Market\Reference\Concerns\HasOnce;

	/** @return TradingService\Marketplace\Model\ShipmentDetails */
	public function getShipment()
	{
		return $this->once('loadShipment');
	}

	/** @noinspection PhpUnused */
	protected function loadShipment()
	{
		$serviceResult = $this->getField('result');

		Market\Reference\Assert::notNull($serviceResult, 'response["result"]');
		Market\Reference\Assert::isArray($serviceResult, 'response["result"]');

		return TradingService\Marketplace\Model\ShipmentDetails::initialize($serviceResult);
	}
}
