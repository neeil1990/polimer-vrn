<?php

namespace Yandex\Market\Trading\Service\MarketplaceDbs\Model\Order;

use Yandex\Market;
use Yandex\Market\Trading\Service as TradingService;

/**
 * @method Delivery\Dates getDates()
*/
class Delivery extends TradingService\Marketplace\Model\Order\Delivery
{
	public function getPartnerType()
	{
		return $this->getField('deliveryPartnerType');
	}

	public function getType()
	{
		return $this->getField('type');
	}

	public function hasShopDeliveryId()
	{
		return $this->hasField('shopDeliveryId') || ($this->hasField('id') && is_numeric($this->getField('id')));
	}

	public function getShopDeliveryId()
	{
		if (!$this->hasField('shopDeliveryId') && $this->hasField('id') && is_numeric($this->getField('id'))) // order info format
		{
			return $this->getRequiredField('id');
		}

		return $this->getRequiredField('shopDeliveryId');
	}

	public function getDispatchType()
	{
		return $this->getField('dispatchType');
	}

	public function getSubsidy()
	{
		return Market\Data\Number::normalize($this->getField('subsidy'));
	}

	public function getLiftType()
	{
		return $this->getField('liftType');
	}

	public function getLiftPrice()
	{
		return Market\Data\Number::normalize($this->getField('liftPrice'));
	}

	/** @return Delivery\Address|null */
	public function getAddress()
	{
		return $this->getChildModel('address');
	}

	public function getOutletStorageLimitDate()
	{
		$value = (string)$this->getField('outletStorageLimitDate');

		return $value !== '' ? Market\Data\Date::convertFromService($value) : null;
	}

	/** @return Delivery\Outlet|null */
	public function getOutlet()
	{
		$result = $this->getChildModel('outlet');

		if ($result !== null) { return $result; }

		if ($this->hasField('outletCode') && (string)$this->getField('outletCode') !== '')
		{
			$reference = $this->getChildModelReference();

			if (!isset($reference['outlet'])) { return null; }

			$modelClassName = $reference['outlet'];
			$relativePath = $this->relativePath . 'outlet.';
			$data = [
				'id' => $this->getField('outletId'),
				'code' => $this->getField('outletCode'),
			];

			$result = $modelClassName::initialize($data, $relativePath);
			$result->setParent($this);

			$this->childModel['outlet'] = $result;
		}

		return $result;
	}

	protected function getChildModelReference()
	{
		$result = [
			'address' => Delivery\Address::class,
			'outlet' => Delivery\Outlet::class,
			'dates' => Delivery\Dates::class,
		];

		return $result + parent::getChildModelReference();
	}
}