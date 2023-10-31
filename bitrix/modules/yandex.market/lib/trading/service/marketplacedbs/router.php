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
			'admin/list' => Action\AdminList\Action::class,
			'admin/view' => Action\AdminView\Action::class,
			'cart' => Action\Cart\Action::class,
			'order/accept' => Action\OrderAccept\Action::class,
			'order/status' => Action\OrderStatus\Action::class,
			'send/status' => Action\SendStatus\Action::class,
			'order/cancellation/notify' => Action\OrderCancellationNotify\Action::class,
			'send/cancellation/accept' => Action\SendCancellationAccept\Action::class,
			'send/delivery/date' => Action\SendDeliveryDate\Action::class,
			'send/delivery/storageLimit' => Action\SendDeliveryStorageLimit\Action::class,
			'send/track' => Action\SendTrack\Action::class,
			'send/items' => Action\SendItems\Action::class,
			'generate/digital' => Action\GenerateDigital\Action::class,
			'send/digital' => Action\SendDigital\Action::class,
			'fill/phone' => Action\FillPhone\Action::class,
			'fill/outlet' => Action\FillOutlet\Action::class,
			'system/cashbox/reset' => Action\SystemCashboxReset\Action::class,
			'settings' => Action\Settings\Action::class,
		];
		$result += array_diff_key(
			parent::getSystemMap(),
			[
				'admin/shipments' => true,
				'send/shipment/confirm' => true,
			]
		);

		return $result;
	}
}
