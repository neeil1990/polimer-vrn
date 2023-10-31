<?php

namespace Yandex\Market\Api\Model\Order;

use Bitrix\Main;
use Yandex\Market;

class BoxItem extends Market\Api\Reference\Model
{
	public function getWeight()
	{
		$result = $this->getField('weight');

		if ((string)$result !== '')
		{
			$result = (int)$result;
		}

		return $result;
	}

	public function getWeightUnit()
	{
		return Market\Data\Weight::UNIT_GRAM;
	}

	public function getCount()
	{
		return (float)$this->getField('count');
	}
}