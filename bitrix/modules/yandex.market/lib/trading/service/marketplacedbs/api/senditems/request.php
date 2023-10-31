<?php

namespace Yandex\Market\Trading\Service\MarketplaceDbs\Api\SendItems;

use Yandex\Market;
use Yandex\Market\Trading\Service as TradingService;

class Request extends TradingService\Marketplace\Api\SendItems\Request
{
	protected $reason;

	public function getQuery()
	{
		return [
			'items' => $this->getItems(),
			'reason' => $this->getReason(),
		];
	}

	public function buildResponse($data)
	{
		return new Response($data + [
			'status' => Response::STATUS_OK,
		]);
	}

	public function getReason()
	{
		Market\Reference\Assert::notNull($this->reason, 'reason');

		return $this->reason;
	}

	public function setReason($reason)
	{
		$this->reason = $reason;
	}
}