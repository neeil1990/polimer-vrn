<?php

namespace Yandex\Market\Trading\Setup;

use Bitrix\Main;
use Yandex\Market;

/** @method getItemById($id) Model */
class Collection extends Market\Reference\Storage\Collection
{
	public static function getItemReference()
	{
		return Model::getClassName();
	}

	public static function loadByService($serviceCode, $behaviorCode = null)
	{
		$filter = [ '=TRADING_SERVICE' => $serviceCode ];

		if ($behaviorCode !== null)
		{
			$filter['=TRADING_BEHAVIOR'] = $behaviorCode;
		}

		return static::loadByFilter([
			'filter' => $filter,
		]);
	}

	public function getBySite($siteId, $filter = null)
	{
		return $this->getByField('SITE_ID', $siteId, $filter);
	}

	public function getActive($filter = null)
	{
		return $this->getByField('ACTIVE', Table::BOOLEAN_Y, $filter);
	}

	public function getByBehavior($behavior, $filter = null)
	{
		return $this->getByField('TRADING_BEHAVIOR', $behavior, $filter);
	}

	protected function getByField($field, $value, $filter = null)
	{
		$result = null;

		/** @var Model $setup*/
		foreach ($this->collection as $setup)
		{
			if ($setup->getField($field) !== $value) { continue; }
			if (!$this->applyFilter($setup, $filter)) { continue; }

			if ($setup->isActive())
			{
				$result = $setup;
				break;
			}

			if ($result === null)
			{
				$result = $setup;
			}
		}

		return $result;
	}
}