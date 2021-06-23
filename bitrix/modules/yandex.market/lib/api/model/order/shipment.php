<?php

namespace Yandex\Market\Api\Model\Order;

use Bitrix\Main;
use Yandex\Market;

class Shipment extends Market\Api\Reference\Model
{
	public function getShipmentDate()
	{
		return Market\Data\Date::convertFromService($this->getField('shipmentDate'));
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