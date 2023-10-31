<?php

namespace Yandex\Market\Api\Model\Order;

use Bitrix\Main;
use Yandex\Market;

class Box extends Market\Api\Reference\Model
{
	public function getFulfilmentId()
	{
		return $this->getField('fulfilmentId');
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

	/**
	 * @deprecated
	 * @return BoxItemCollection|null
	 */
	public function getItems()
	{
		return $this->getChildCollection('items');
	}

	protected function getChildCollectionReference()
	{
		return [
			'items' => BoxItemCollection::class
		];
	}
}