<?php

namespace Yandex\Market\Trading\Service\MarketplaceDbs\Api\Buyer;

use Yandex\Market;
use Yandex\Market\Trading\Service as TradingService;

class Response extends Market\Api\Reference\ResponseWithResult
{
	use Market\Reference\Concerns\HasOnce;

	/** @return TradingService\MarketplaceDbs\Model\Order\Buyer */
	public function getBuyer()
	{
		return $this->once('loadBuyer');
	}

	protected function loadBuyer()
	{
		$data = (array)$this->getField('result');

		return new TradingService\MarketplaceDbs\Model\Order\Buyer($data);
	}
}