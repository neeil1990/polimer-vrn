<?php

namespace Yandex\Market\Trading\Service\Common\Action;

use Yandex\Market\Trading\Service as TradingService;

class SendRequest extends TradingService\Reference\Action\DataRequest
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

	public function isAutoSubmit()
	{
		return (bool)$this->getField('autoSubmit');
	}

	public function getImmediate()
	{
		return (bool)$this->getField('immediate');
	}
}