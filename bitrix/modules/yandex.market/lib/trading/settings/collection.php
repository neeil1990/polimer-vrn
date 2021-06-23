<?php

namespace Yandex\Market\Trading\Settings;

use Bitrix\Main;
use Yandex\Market;

class Collection extends Market\Reference\Storage\Collection
{
	public static function getItemReference()
	{
		return Model::getClassName();
	}

	public function getValues()
	{
		$result = [];

		/** @var Market\Trading\Settings\Model $model */
		foreach ($this->collection as $model)
		{
			$result[$model->getName()] = $model->getValue();
		}

		return $result;
	}
}