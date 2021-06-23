<?php

namespace Yandex\Market\Api\Model\Cart;

use Yandex\Market;
use Bitrix\Main;

class Delivery extends Market\Api\Reference\Model
{
	/**
	 * @return Market\Api\Model\Region
	 * @throws Market\Exceptions\Api\InvalidOperation
	 */
	public function getRegion()
	{
		return $this->getRequiredModel('region');
	}

	protected function getChildModelReference()
	{
		return [
			'region' => Market\Api\Model\Region::class,
		];
	}
}