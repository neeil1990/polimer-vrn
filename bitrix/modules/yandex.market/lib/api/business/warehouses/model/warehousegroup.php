<?php
/** @noinspection PhpIncompatibleReturnTypeInspection */
/** @noinspection PhpReturnDocTypeMismatchInspection */
namespace Yandex\Market\Api\Business\Warehouses\Model;

use Yandex\Market;

class WarehouseGroup extends Market\Api\Reference\Model
{
	public function getName()
	{
		return (string)$this->getRequiredField('name');
	}

	/** @return Warehouse */
	public function getMainWarehouse()
	{
		return $this->getRequiredModel('mainWarehouse');
	}

	/** @return WarehouseCollection */
	public function getWarehouses()
	{
		return $this->getRequiredCollection('warehouses');
	}

	protected function getChildModelReference()
	{
		return [
			'mainWarehouse' => Warehouse::class,
		];
	}

	protected function getChildCollectionReference()
	{
		return [
			'warehouses' => WarehouseCollection::class,
		];
	}
}