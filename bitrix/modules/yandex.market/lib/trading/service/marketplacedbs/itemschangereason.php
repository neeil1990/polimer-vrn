<?php

namespace Yandex\Market\Trading\Service\MarketplaceDbs;

use Yandex\Market;
use Yandex\Market\Trading\Service as TradingService;

class ItemsChangeReason extends TradingService\Reference\ItemsChangeReason
{
	use Market\Reference\Concerns\HasMessage;

	const USER_REQUESTED_REMOVE = 'USER_REQUESTED_REMOVE';
	const PARTNER_REQUESTED_REMOVE = 'PARTNER_REQUESTED_REMOVE';

	protected $provider;

	public function getDefault()
	{
		return static::USER_REQUESTED_REMOVE;
	}

	public function getTitle($type)
	{
		return static::getMessage($type, null, $type);
	}

	public function getVariants()
	{
		return [
			static::PARTNER_REQUESTED_REMOVE,
			static::USER_REQUESTED_REMOVE,
		];
	}
}