<?php

namespace Yandex\Market\Api\Model;

use Yandex\Market;
use Bitrix\Main;

class Cart extends Market\Api\Reference\Model
{
	public function getCurrency()
	{
		return (string)$this->getRequiredField('currency');
	}

	public function hasDelivery()
	{
		return $this->hasField('delivery');
	}

	/**
	 * @return Cart\Delivery
	 * @throws Main\ObjectPropertyException
	 */
	public function getDelivery()
	{
		return $this->getRequiredModel('delivery');
	}

	/**
	 * @return Cart\ItemCollection
	 * @throws Main\ObjectPropertyException
	 */
	public function getItems()
	{
		return $this->getRequiredCollection('items');
	}

	protected function getChildModelReference()
	{
		return [
			'delivery' => Cart\Delivery::class,
		];
	}

	protected function getChildCollectionReference()
	{
		return [
			'items' => Cart\ItemCollection::class,
		];
	}
}