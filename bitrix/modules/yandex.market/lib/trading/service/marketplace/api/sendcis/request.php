<?php

namespace Yandex\Market\Trading\Service\Marketplace\Api\SendCis;

use Bitrix\Main;
use Yandex\Market;

class Request extends Market\Api\Partner\Reference\Request
{
	protected $orderId;
	protected $items;

	public function getPath()
	{
		return sprintf(
			'/v2/campaigns/%s/orders/%s/cis.json',
			$this->getCampaignId(),
			$this->getOrderId()
		);
	}

	public function getQuery()
	{
		return [
			'items' => $this->getItems(),
		];
	}

	public function getMethod()
	{
		return Main\Web\HttpClient::HTTP_PUT;
	}

	public function getQueryFormat()
	{
		return static::DATA_TYPE_JSON;
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

	public function setItems($items)
	{
		$this->items = $items;
	}

	public function getItems()
	{
		if ($this->items === null)
		{
			throw new Main\SystemException('items set');
		}

		return (array)$this->items;
	}
}