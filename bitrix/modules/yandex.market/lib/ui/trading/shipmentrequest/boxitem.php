<?php

namespace Yandex\Market\Ui\Trading\ShipmentRequest;

use Bitrix\Main;
use Yandex\Market;

/** @deprecated */
class BoxItem extends Market\Api\Reference\Model
{
	public function getId()
	{
		return (int)$this->getRequiredField('ID');
	}

	public function getCount()
	{
		$result = (float)$this->getRequiredField('COUNT');

		if ($result <= 0)
		{
			throw new Market\Exceptions\Api\ObjectPropertyException($this->relativePath . 'COUNT');
		}

		return $result;
	}
}