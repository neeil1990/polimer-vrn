<?php

namespace Yandex\Market\Trading\Service\MarketplaceDbs;

use Yandex\Market;
use Yandex\Market\Trading\Service as TradingService;

class CancellationAccept extends TradingService\Reference\CancellationAccept
{
	use Market\Reference\Concerns\HasMessage;

	const ORDER_DELIVERED = 'ORDER_DELIVERED';

	public function getDefault()
	{
		return static::ORDER_DELIVERED;
	}

	public function getReasonTitle($type)
	{
		return static::getMessage('REASON_' . $type, null, $type);
	}

	public function getReasonVariants()
	{
		return [
			static::ORDER_DELIVERED,
		];
	}
}