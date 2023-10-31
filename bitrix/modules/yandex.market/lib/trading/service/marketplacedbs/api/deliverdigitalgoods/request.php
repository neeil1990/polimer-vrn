<?php

namespace Yandex\Market\Trading\Service\MarketplaceDbs\Api\DeliverDigitalGoods;

use Bitrix\Main;
use Yandex\Market;

class Request extends Market\Api\Partner\Reference\Request
{
	protected $orderId;
	protected $items;

	public function getPath()
	{
		return sprintf(
			'/v2/campaigns/%s/orders/%s/deliverDigitalGoods.json',
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
		return Main\Web\HttpClient::HTTP_POST;
	}

	public function getQueryFormat()
	{
		return static::DATA_TYPE_JSON;
	}

	public function buildResponse($data)
	{
		return new Response($data + [
			'status' => Response::STATUS_OK,
		]);
	}

	public function setOrderId($orderId)
	{
		$this->orderId = $orderId;
	}

	public function getOrderId()
	{
		Market\Reference\Assert::notNull($this->orderId, 'orderId');

		return (string)$this->orderId;
	}

	public function setItems($items)
	{
		$this->items = $items;
	}

	public function getItems()
	{
		Market\Reference\Assert::notNull($this->items, 'items');

		return (array)$this->items;
	}
}