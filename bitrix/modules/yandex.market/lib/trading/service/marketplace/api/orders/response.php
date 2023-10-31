<?php

namespace Yandex\Market\Trading\Service\Marketplace\Api\Orders;

use Bitrix\Main;
use Yandex\Market;
use Yandex\Market\Trading\Service as TradingService;

class Response extends Market\Api\Partner\Orders\Response
{
	protected function loadOrderCollection()
	{
		$dataList = (array)$this->getField('orders');
		$pager = $this->getPager();

		$collection = TradingService\Marketplace\Model\OrderCollection::initialize($dataList);
		$collection->setPager($pager);

		return $collection;
	}
}
