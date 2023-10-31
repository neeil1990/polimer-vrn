<?php
/**
 * @noinspection PhpIncompatibleReturnTypeInspection
 * @noinspection PhpReturnDocTypeMismatchInspection
 */
namespace Yandex\Market\SalesBoost\Product;

use Yandex\Market\SalesBoost;
use Yandex\Market\Export;
use Yandex\Market\Reference;

/**
 * @method SalesBoost\Setup\Model getParent()
 */
class Model extends Reference\Storage\Model
{
	public static function getDataClass()
	{
		return Table::class;
	}

	/** @return Export\Filter\Collection */
	public function getFilterCollection()
	{
		return $this->getChildCollection('FILTER');
	}

	public function getIblockId()
	{
		return (int)$this->getField('IBLOCK_ID');
	}

	public function getContext()
	{
		return Export\Entity\Iblock\Provider::getContext($this->getIblockId());
	}

	public function getTrackSourceList()
	{
		$sourceList = $this->getUsedSources();
		$context = $this->getContext();
		$result = [];

		foreach ($sourceList as $sourceType)
		{
			$eventHandler = Export\Entity\Manager::getEvent($sourceType);

			$result[] = [
				'SOURCE_TYPE' => $sourceType,
				'SOURCE_PARAMS' => $eventHandler->getSourceParams($context)
			];
		}

		return $result;
	}

	protected function getUsedSources()
	{
		$used = $this->getUsedSelectSources() + $this->getUsedFilterSources();

		return array_keys($used);
	}

	protected function getUsedSelectSources()
	{
		$fields = $this->getParent()->getBidFields();

		return array_column($fields, 'SOURCE', 'SOURCE');
	}

	protected function getUsedFilterSources()
	{
		$result = [];

		foreach ($this->getFilterCollection() as $filterModel)
		{
			$filterUserSources = $filterModel->getUsedSources();

			$result += array_flip($filterUserSources);
		}

		return $result;
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