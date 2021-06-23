<?php

namespace Yandex\Market\Trading\Service\MarketplaceDbs;

use Yandex\Market;
use Bitrix\Main;
use Yandex\Market\Trading\Service as TradingService;

class Printer extends Market\Trading\Service\Marketplace\Printer
{
	protected function getSystemMap()
	{
		return [];
	}
}
