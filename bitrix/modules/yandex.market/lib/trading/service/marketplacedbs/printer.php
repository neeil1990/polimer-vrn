<?php

namespace Yandex\Market\Trading\Service\MarketplaceDbs;

use Yandex\Market;

class Printer extends Market\Trading\Service\Marketplace\Printer
{
	protected function getSystemMap()
	{
		return [
			'boxLabel' => Document\BoxLabel::class,
		];
	}
}
