<?php
namespace Yandex\Market\Trading\Business;

use Yandex\Market;

/** @method Model getItemById($id) */
class Collection extends Market\Reference\Storage\Collection
{
	public static function getItemReference()
	{
		return Model::class;
	}

	public function getActive($filter = null)
	{
		return $this->getByField('ACTIVE', Table::BOOLEAN_Y, $filter);
	}

	/** @noinspection DuplicatedCode */
	protected function getByField($field, $value, $filter = null)
	{
		$result = null;

		/** @var Model $setup */
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