<?php

namespace Yandex\Market\Trading\Entity\Sale\Outlet\IpolSdek;

use Bitrix\Sale;
use Yandex\Market;
use Yandex\Market\Reference\Concerns;

/** @noinspection PhpUnused */
class Pvz extends Skeleton
{
	use Concerns\HasMessage;

	public function getTitle()
	{
		return self::getMessage('TITLE');
	}

	public function isMatch($deliveryId)
	{
		$deliveryService = $this->environment->getDelivery()->getDeliveryService($deliveryId);

		if (!($deliveryService instanceof Sale\Delivery\Services\AutomaticProfile)) { return false; }

		return $deliveryService->getCode() === 'sdek:pickup';
	}

	protected function pickupType()
	{
		return 'PVZ';
	}

	protected function filterOutlets(array $outlets)
	{
		$weight = !empty(\CDeliverySDEK::$orderWeight) ? false : \Ipolh\SDEK\option::get('weightD');

		return \CDeliverySDEK::weightPVZ($weight, $outlets);
	}

	protected function tariffId()
	{
		return Market\Config::getOption('trading_outlet_sdek_postamat_tariff', 136);
	}
}