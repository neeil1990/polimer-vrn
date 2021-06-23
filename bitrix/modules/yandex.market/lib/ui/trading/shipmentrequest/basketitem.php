<?php

namespace Yandex\Market\Ui\Trading\ShipmentRequest;

use Bitrix\Main;
use Yandex\Market;

class BasketItem extends Market\Api\Reference\Model
{
	/** @return string */
	public function getId()
	{
		return (string)$this->getRequiredField('ID');
	}

	/** @return string[] */
	public function getCis()
	{
		$values = (array)$this->getField('CIS');
		$values = array_map('trim', $values);

		return array_filter($values, static function($value) { return $value !== ''; });
	}
}