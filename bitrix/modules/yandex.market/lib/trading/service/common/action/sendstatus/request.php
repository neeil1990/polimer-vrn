<?php

namespace Yandex\Market\Trading\Service\Common\Action\SendStatus;

use Yandex\Market;
use Bitrix\Main;

class Request extends Market\Trading\Service\Reference\Action\DataRequest
{
	public function getInternalId()
	{
		return (string)$this->getRequiredField('internalId');
	}

	public function getOrderId()
	{
		return (string)$this->getRequiredField('orderId');
	}

	public function getOrderNumber()
	{
		return (string)$this->getRequiredField('orderNum');
	}

	public function getStatus()
	{
		return (string)$this->getRequiredField('status');
	}

	public function getImmediate()
	{
		return (bool)$this->getField('immediate');
	}
}