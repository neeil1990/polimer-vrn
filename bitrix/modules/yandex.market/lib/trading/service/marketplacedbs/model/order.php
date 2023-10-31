<?php

namespace Yandex\Market\Trading\Service\MarketplaceDbs\Model;

use Yandex\Market;
use Bitrix\Main;
use Yandex\Market\Trading\Service as TradingService;

/**
 * @method Order\Delivery getDelivery()
 */
class Order extends TradingService\Marketplace\Model\Order
{
	public static function getMeaningfulFields()
	{
		return array_diff(
			parent::getMeaningfulFields(),
			[
				'DATE_EXPIRY',
				'DATE_SHIPMENT',
				'EAC_CODE',
				'VEHICLE_NUMBER',
			]
		);
	}

	public function getPaymentMethod()
	{
		return (string)$this->getRequiredField('paymentMethod');
	}

	/** @return Order\Buyer|null */
	public function getBuyer()
	{
		return $this->getChildModel('buyer');
	}

	protected function getChildModelReference()
	{
		return [
			'delivery' => Order\Delivery::class,
			'buyer' => Order\Buyer::class,
		];
	}

	public function getMeaningfulValues()
	{
		return array_diff_key(
			parent::getMeaningfulValues(),
			[ 'DATE_SHIPMENT' => true ]
		);
	}
}