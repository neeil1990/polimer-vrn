<?php
/**
 * @noinspection PhpIncompatibleReturnTypeInspection
 * @noinspection PhpReturnDocTypeMismatchInspection
 */
namespace Yandex\Market\Export\CollectionProduct;

use Yandex\Market;
use Yandex\Market\Reference;
use Yandex\Market\Export;

class Model extends Reference\Storage\Model
{
	use Reference\Concerns\HasOnce;

	protected $filterCollection;

	public static function getDataClass()
	{
		return Table::class;
	}

	public function getIblockId()
	{
		return (int)$this->getField('IBLOCK_ID');
	}

	public function getContext()
	{
		return Export\Entity\Iblock\Provider::getContext($this->getIblockId());
	}

	/** @return Export\Filter\Collection */
	public function getFilterCollection()
	{
		if ($this->filterCollection !== null)
		{
			return $this->filterCollection;
		}

		return $this->getChildCollection('FILTER');
	}

	public function setFilterCollection(Export\Filter\Collection $filterCollection)
	{
		$this->filterCollection = $filterCollection;
	}

	protected function getChildCollectionReference($fieldKey)
	{
		if ($fieldKey === 'FILTER')
		{
			return Export\Filter\Collection::class;
		}

		return null;
	}
}