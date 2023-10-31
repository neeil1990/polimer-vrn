<?php

namespace Yandex\Market\Trading\Service\Marketplace\Action\AdminView;

use Yandex\Market;
use Bitrix\Main;
use Yandex\Market\Trading\Service as TradingService;

class Request extends TradingService\Reference\Action\DataRequest
{
	public function getOrderId()
	{
		return (string)$this->getRequiredField('id');
	}

	public function useCache()
	{
		return (bool)$this->getField('useCache');
	}

	public function flushCache()
	{
		return (bool)$this->getField('flushCache');
	}

	public function getUserId()
	{
		return (int)$this->getField('userId');
	}

	public function needCheckAccess()
	{
		return (bool)$this->getField('checkAccess');
	}
}