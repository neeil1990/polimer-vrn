<?php

namespace Yandex\Market\Trading\Service\Marketplace\Model;

use Yandex\Market;
use Bitrix\Main;
use Yandex\Market\Trading\Service as TradingService;

class Order extends Market\Api\Model\Order
{
	protected static function includeMessages()
	{
		Main\Localization\Loc::loadMessages(__FILE__);
		parent::includeMessages();
	}

	public static function getMeaningfulFields()
	{
		$result = parent::getMeaningfulFields();
		$result[] = 'DATE_EXPIRY';
		$result[] = 'DATE_SHIPMENT';
		$result[] = 'EAC_CODE';

		return $result;
	}

	public static function getMeaningfulFieldTitle($fieldName)
	{
		$result = static::getLang('TRADING_ACTION_MODEL_ORDER_FIELD_' . $fieldName, null, '');

		if ($result === '')
		{
			$result = parent::getMeaningfulFieldTitle($fieldName);
		}

		return $result;
	}

	public static function getMeaningfulFieldHelp($fieldName)
	{
		$result = static::getLang('TRADING_ACTION_MODEL_ORDER_HELP_' . $fieldName, null, '');

		if ($result === '')
		{
			$result = parent::getMeaningfulFieldHelp($fieldName);
		}

		return $result;
	}

	/** @return Order\Buyer|null */
	public function getBuyer()
	{
		return $this->getChildModel('buyer');
	}

	/** @return Order\Delivery */
	public function getDelivery()
	{
		return $this->getRequiredModel('delivery');
	}

	/**
	 * @return Order\ItemCollection
	 * @throws Main\ObjectPropertyException
	 */
	public function getItems()
	{
		return $this->getRequiredCollection('items');
	}

	protected function getChildModelReference()
	{
		return array_merge(parent::getChildModelReference(), [
			'buyer' => Order\Buyer::class,
			'delivery' => Order\Delivery::class
		]);
	}

	protected function getChildCollectionReference()
	{
		return array_merge(parent::getChildCollectionReference(), [
			'items' => Order\ItemCollection::class,
		]);
	}

	public function getMeaningfulValues()
	{
		$result = parent::getMeaningfulValues();
		$result += array_filter([
			'DATE_EXPIRY' => $this->getExpiryDate(),
			'DATE_SHIPMENT' => $this->getMeaningfulShipmentDates(),
			'EAC_CODE' => $this->getDelivery()->getEacCode(),
		]);

		return $result;
	}

	/**
	 * @return Main\Type\Date[]
	 */
	public function getMeaningfulShipmentDates()
	{
		$result = [];

		if ($this->hasDelivery())
		{
			/** @var Market\Api\Model\Order\Shipment $shipment */
			foreach ($this->getDelivery()->getShipments() as $shipment)
			{
				$date = $shipment->getShipmentDate();

				if ($date !== null)
				{
					$result[] = $date;
				}
			}
		}

		return $result;
	}

	public function getExpiryDate()
	{
		$value = $this->getField('expiryDate');

		return $value !== null ? Market\Data\DateTime::convertFromService($value) : null;
	}
}