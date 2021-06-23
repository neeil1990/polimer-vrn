<?php

namespace Yandex\Market\Api\Partner\Order;

use Bitrix\Main;
use Yandex\Market;

class Response extends Market\Api\Partner\Reference\Response
{
	protected $order;

	public function validate()
	{
		$result = parent::validate();

		if (!$result->isSuccess())
		{
			// nothing
		}
		else if ($orderError = $this->validateOrder())
		{
			$result->addError($orderError);
		}

		return $result;
	}

	public function getOrder()
	{
		if ($this->order === null)
		{
			$this->order = $this->loadOrder();
		}

		return $this->order;
	}

	protected function loadOrder()
	{
		$data = (array)$this->getField('order');

		return new Market\Api\Model\Order($data);
	}

	protected function validateOrder()
	{
		$result = null;
		$order = $this->getOrder();

		if ((string)$order->getId() === '')
		{
			$message = Market\Config::getLang('API_ORDER_RESPONSE_HASNT_ORDER');
			$result = new Main\Error($message);
		}

		return $result;
	}
}