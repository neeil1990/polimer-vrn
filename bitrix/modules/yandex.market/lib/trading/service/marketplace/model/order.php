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
		$result[] = 'DATE_SHIPMENT';

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

	/**
	 * @return Order\ItemCollection
	 * @throws Main\ObjectPropertyException
	 */
	public function getItems()
	{
		return $this->getRequiredCollection('items');
	}

	protected function getChildCollectionReference()
	{
		return [
			'items' => Order\ItemCollection::class,
		];
	}

	public function getMeaningfulValues()
	{
		$result = parent::getMeaningfulValues();
		$result += array_filter([
			'DATE_SHIPMENT' => $this->getMeaningfulShipmentDates(),
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
}