<?php
/** @noinspection PhpIncompatibleReturnTypeInspection */
/** @noinspection PhpReturnDocTypeMismatchInspection */
namespace Yandex\Market\Api\Business\Warehouses;

use Yandex\Market;

class Response extends Market\Api\Reference\ResponseWithResult
{
	/** @return Model\WarehouseCollection|null */
	public function getWarehouses()
	{
		return $this->getChildCollection('result.warehouses');
	}

	/** @return Model\WarehouseGroupCollection|null */
	public function getWarehouseGroups()
	{
		return $this->getChildCollection('result.warehouseGroups');
	}

	protected function getChildCollectionReference()
	{
		return [
			'result.warehouses' => Model\WarehouseCollection::class,
			'result.warehouseGroups' => Model\WarehouseGroupCollection::class,
		];
	}
}