<?php

namespace Yandex\Market\Api\Model\Order;

use Bitrix\Main;
use Yandex\Market;

class Shipment extends Market\Api\Reference\Model
{
	public function getShipmentDate()
	{
		if (!$this->hasField('shipmentDate')) { return null; }

		$date = Market\Data\Date::convertFromService($this->getField('shipmentDate'));
		$time = (string)$this->getShipmentTime();

		if ($time === '') { return $date; }

		$timeParts = Market\Data\Time::parse($time);

		if ($timeParts === null) { return $date; }

		$dateWithTime = Main\Type\DateTime::createFromTimestamp($date->getTimestamp());
		$dateWithTime->setTime($timeParts[0], $timeParts[1]);

		return $dateWithTime;
	}

	public function getShipmentTime()
	{
		return $this->getField('shipmentTime');
	}

	public function getWidth()
	{
		return Market\Data\Number::normalize($this->getField('width'));
	}

	public function getHeight()
	{
		return Market\Data\Number::normalize($this->getField('height'));
	}

	public function getDepth()
	{
		return Market\Data\Number::normalize($this->getField('depth'));
	}

	public function getSizeUnit()
	{
		return Market\Data\Size::UNIT_CENTIMETER;
	}

	public function getWeight()
	{
		return Market\Data\Number::normalize($this->getField('weight'));
	}

	public function getWeightUnit()
	{
		return Market\Data\Weight::UNIT_GRAM;
	}

	public function hasSavedBoxes()
	{
		return parent::hasField('boxes');
	}

	/**
	 * @return BoxCollection|null
	 */
	public function getBoxes()
	{
		return $this->getChildCollection('boxes');
	}

	protected function getChildCollectionReference()
	{
		return [
			'boxes' => BoxCollection::class
		];
	}

	public function hasField($name)
	{
		$result = parent::hasField($name);

		if ($result === false && $name === 'boxes' && $this->hasField('items')) // convert old boxes format to new
		{
			$result = true;
		}

		return $result;
	}

	public function getField($name)
	{
		$result = parent::getField($name);

		if ($name === 'boxes' && $result === null && $this->hasField('items')) // convert old boxes format to new
		{
			$virtualBox = array_intersect_key($this->getFields(), [
				'items' => true,
				'weight' => true,
				'width' => true,
				'height' => true,
				'depth' => true,
			]);

			$result = [
				$virtualBox
			];
		}

		return $result;
	}
}