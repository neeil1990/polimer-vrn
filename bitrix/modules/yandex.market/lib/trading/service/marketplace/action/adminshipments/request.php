<?php

namespace Yandex\Market\Trading\Service\Marketplace\Action\AdminShipments;

use Yandex\Market;
use Bitrix\Main;
use Yandex\Market\Trading\Service as TradingService;

class Request extends TradingService\Reference\Action\DataRequest
{
	public function getPage()
	{
		return max(1, (int)$this->getField('page'));
	}

	public function getParameters()
	{
		return array_intersect_key($this->getFields(), [
			'pageToken' => true,
			'dateFrom' => true,
			'dateTo' => true,
			'statuses' => true,
			'orderIds' => true,
			'page' => true,
			'pageSize' => true,
		]);
	}
}