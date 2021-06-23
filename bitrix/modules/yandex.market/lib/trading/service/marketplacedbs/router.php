<?php

namespace Yandex\Market\Trading\Service\MarketplaceDbs;

use Yandex\Market;
use Bitrix\Main;
use Yandex\Market\Trading\Service as TradingService;

class Router extends Market\Trading\Service\Marketplace\Router
{
	protected function getSystemMap()
	{
		$result = [
			'cart' => Action\Cart\Action::class,
			'order/accept' => Action\OrderAccept\Action::class,
			'order/status' => Action\OrderStatus\Action::class,
			'send/status' => Action\SendStatus\Action::class,
		];
		$result += array_diff_key(
			parent::getSystemMap(),
			[
				'send/boxes' => true,
			]
		);

		return $result;
	}
}
