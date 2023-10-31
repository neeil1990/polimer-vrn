<?php

namespace Yandex\Market\Trading\Service\MarketplaceDbs\Api\Buyer;

use Yandex\Market;

class Request extends Market\Api\Partner\Reference\Request
{
	protected $orderId;

	public function getPath()
	{
		return '/v2/campaigns/' . $this->getCampaignId() . '/orders/' . $this->getOrderId() .'/buyer.json';
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
		Market\Reference\Assert::notNull($this->orderId, 'orderId');

		return (string)$this->orderId;
	}
}