<?php

namespace Yandex\Market\Trading\Service\Marketplace\Api\ShipmentOrdersInfo;

use Yandex\Market;
use Yandex\Market\Trading\Service as TradingService;

class Response extends Market\Api\Reference\ResponseWithResult
{
	use Market\Reference\Concerns\HasOnce;

	/** @return TradingService\Marketplace\Model\ShipmentOrdersInfo */
	public function getOrdersInfo()
	{
		return $this->once('loadOrdersInfo');
	}

	/** @noinspection PhpUnused */
	protected function loadOrdersInfo()
	{
		$serviceResult = $this->getField('result');

		Market\Reference\Assert::notNull($serviceResult, 'response["result"]');
		Market\Reference\Assert::isArray($serviceResult, 'response["result"]');

		return TradingService\Marketplace\Model\ShipmentOrdersInfo::initialize($serviceResult);
	}
}
