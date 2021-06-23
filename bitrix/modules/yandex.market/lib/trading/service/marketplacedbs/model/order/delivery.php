<?php

namespace Yandex\Market\Trading\Service\MarketplaceDbs\Model\Order;

use Yandex\Market;
use Bitrix\Main;
use Yandex\Market\Trading\Service as TradingService;

class Delivery extends Market\Api\Model\Order\Delivery
{
	public function getPartnerType()
	{
		return $this->getField('deliveryPartnerType');
	}

	public function getType()
	{
		return $this->getField('type');
	}

	public function getShopDeliveryId()
	{
		return $this->getRequiredField('shopDeliveryId');
	}

	/** @return Delivery\Address|null */
	public function getAddress()
	{
		return $this->getChildModel('address');
	}

	/** @return Delivery\Dates|null */
	public function getDates()
	{
		return $this->getChildModel('dates');
	}

	/** @return Delivery\Outlet|null */
	public function getOutlet()
	{
		return $this->getChildModel('outlet');
	}

	protected function getChildModelReference()
	{
		$result = [
			'address' => Delivery\Address::class,
			'dates' => Delivery\Dates::class,
			'outlet' => Delivery\Outlet::class,
		];

		return $result + parent::getChildModelReference();
	}
}