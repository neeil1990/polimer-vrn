<?php

namespace Yandex\Market\Trading\Service\Marketplace;

use Yandex\Market;
use Yandex\Market\Trading\Service as TradingService;

class Router extends Market\Trading\Service\Reference\Router
{
	/** @noinspection PhpDeprecationInspection */
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
			'admin/shipments' => Action\AdminShipments\Action::class,
			'stocks' => Action\Stocks\Action::class,
			'push/stocks' => Action\PushStocks\Action::class,
			'push/prices' => Action\PushPrices\Action::class,
			'send/status' => Action\SendStatus\Action::class,
			'send/items' => Action\SendItems\Action::class,
			'send/boxes' => Action\SendBoxes\Action::class,
			'send/cis' => Action\SendCis\Action::class,
			'send/identifiers' => Action\SendIdentifiers\Action::class,
			'send/shipment/confirm' => Action\SendShipmentConfirm\Action::class,
			'send/shipment/excludeOrders' => Action\SendShipmentExcludeOrders\Action::class,
			'send/verifyEac' => Action\VerifyEac\Action::class,
			'system/cashbox/reset' => Action\SystemCashboxReset\Action::class,
			'settings' => TradingService\Common\Action\Settings\Action::class,
			'settings/log' => TradingService\Common\Action\SettingsLog\Action::class,
		];
	}
}
