<?php

namespace Yandex\Market\Trading\Service\Marketplace\Api\BoxLabels;

use Bitrix\Main;
use Yandex\Market;

class Request extends Market\Api\Partner\Reference\Request
{
	protected $orderId;

	public function getPath()
	{
		return '/v2/campaigns/' . $this->getCampaignId() . '/orders/' . $this->getOrderId() .'/delivery/labels/data.json';
	}

	public function buildResponse($data)
	{
		return new Response($data);
	}

	public function setOrderId($orderId)
	{
		$this->orderId = $orderId;
	}

	public function getOrderId()
	{
		if ($this->orderId === null)
		{
			throw new Main\SystemException('orderId not set');
		}

		return (string)$this->orderId;
	}
}