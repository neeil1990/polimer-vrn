<?php

namespace Yandex\Market\Trading\Service\MarketplaceDbs\Document;

use Yandex\Market\Trading\Service as TradingService;

class BoxLabel extends TradingService\Marketplace\Document\BoxLabel
{
	public function getFilter()
	{
		return [
			'DISPATCH_TYPE' => [
				TradingService\MarketplaceDbs\Delivery::DISPATCH_TYPE_MARKET_BRANDED_OUTLET,
				TradingService\MarketplaceDbs\Delivery::DISPATCH_TYPE_MARKET_PARTNER_OUTLET,
			],
		];
	}
}