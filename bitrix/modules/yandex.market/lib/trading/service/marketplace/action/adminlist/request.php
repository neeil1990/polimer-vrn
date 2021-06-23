<?php

namespace Yandex\Market\Trading\Service\Marketplace\Action\AdminList;

use Yandex\Market;
use Bitrix\Main;
use Yandex\Market\Trading\Service as TradingService;

class Request extends TradingService\Reference\Action\DataRequest
{
	public function hasPrimaries()
	{
		return $this->hasField('id');
	}

	public function getPrimaries()
	{
		return (array)$this->getField('id');
	}

	public function onlyPrintReady()
	{
		return (bool)$this->getField('printReady');
	}

	public function getUserId()
	{
		return (int)$this->getField('userId');
	}

	public function needCheckAccess()
	{
		return (bool)$this->getField('checkAccess');
	}

	public function getParameters()
	{
		return array_intersect_key($this->getFields(), [
			'status' => true,
			'fromDate' => true,
			'toDate' => true,
			'fromShipmentDate' => true,
			'toShipmentDate' => true,
			'fake' => true,
			'page' => true,
			'pageSize' => true,
		]);
	}

	public function useCache()
	{
		return (bool)$this->getField('useCache');
	}

	public function flushCache()
	{
		return (bool)$this->getField('flushCache');
	}
}