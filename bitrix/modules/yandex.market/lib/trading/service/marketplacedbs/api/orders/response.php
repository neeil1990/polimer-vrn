<?php

namespace Yandex\Market\Trading\Service\MarketplaceDbs\Api\Orders;

use Yandex\Market;
use Yandex\Market\Trading\Service as TradingService;

class Response extends Market\Api\Partner\Orders\Response
{
	protected function loadOrderCollection()
	{
		$dataList = (array)$this->getField('orders');
		$pager = $this->getPager();

		$collection = TradingService\MarketplaceDbs\Model\OrderCollection::initialize($dataList);
		$collection->setPager($pager);

		return $collection;
	}
}
