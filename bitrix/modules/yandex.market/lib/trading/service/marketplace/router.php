<?php

namespace Yandex\Market\Trading\Service\Marketplace;

use Yandex\Market;
use Bitrix\Main;
use Yandex\Market\Trading\Service as TradingService;

class Router extends Market\Trading\Service\Reference\Router
{
	protected function getSystemMap()
	{
		return [
			'root' => TradingService\Common\Action\Root\Action::class,
			'hello' => TradingService\Common\Action\Hello\Action::class,
			'cart' => Action\Cart\Action::class,
			'order/accept' => Action\OrderAccept\Action::class,
			'order/status' => Action\OrderStatus\Action::class,
			'admin/list' => Action\AdminList\Action::class,
			'admin/view' => Action\AdminView\Action::class,
			'stocks' => Action\Stocks\Action::class,
			'send/status' => Action\SendStatus\Action::class,
			'send/boxes' => Action\SendBoxes\Action::class,
			'send/cis' => Action\SendCis\Action::class,
		];
	}
}
