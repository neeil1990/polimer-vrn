<?php

namespace Yandex\Market\Api\Partner\SendStatus;

use Yandex\Market;

class Response extends Market\Api\Partner\Reference\Response
{
	protected $order;

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
		$data = (array)$this->getRequiredField('order');

		return new Market\Api\Model\Order($data);
	}
}