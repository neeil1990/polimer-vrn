<?php

namespace Yandex\Market\Trading\Service\Marketplace\Api\SendIdentifiers;

use Bitrix\Main;
use Yandex\Market;

class Request extends Market\Api\Partner\Reference\Request
{
	protected $orderId;
	protected $items;

	public function getPath()
	{
		return sprintf(
			'/v2/campaigns/%s/orders/%s/identifiers.json',
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
		Market\Reference\Assert::notNull($this->items, 'orderId');

		return (string)$this->orderId;
	}

	public function setItems(array $items)
	{
		$this->items = $items;
	}

	public function getItems()
	{
		Market\Reference\Assert::notNull($this->items, 'items');

		return (array)$this->items;
	}
}