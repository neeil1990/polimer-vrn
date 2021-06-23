<?php

namespace Yandex\Market\Export\Filter;

use Yandex\Market;

class Model extends Market\Reference\Storage\Model
{
	/** @var array|null */
	protected $plainFilter;
	/** @var array|null */
	protected $plainData;

	public static function getDataClass()
	{
		return Table::getClassName();
	}

	public function getPlainFilter()
	{
		return $this->plainFilter;
	}

	public function setPlainFilter(array $plainFilter)
	{
		$this->plainFilter = $plainFilter;
	}

	public function getPlainData()
	{
		return $this->plainData;
	}

	public function setPlainData(array $data)
	{
		$this->plainData = $data;
	}

	public function getSourceFilter()
	{
		if ($this->plainFilter !== null)
		{
			$result = $this->plainFilter;
		}
		else
		{
			$result = [];

			/** @var \Yandex\Market\Export\FilterCondition\Model $condition */
			foreach ($this->getConditionCollection() as $condition)
			{
				if ($condition->isValid())
				{
					$conditionCompare = $condition->getQueryCompare();
					$conditionField = $condition->getQueryField();
					$conditionValue = $condition->getQueryValue();
					$conditionSource = $condition->getSourceName();

					if (!isset($result[$conditionSource]))
					{
						$result[$conditionSource] = [];
					}

					$result[$conditionSource][] = [
						'FIELD' => $conditionField,
						'COMPARE' => $conditionCompare,
						'VALUE' => $conditionValue,
						'STRICT' => $condition->isQueryCompareStrict(),
					];
				}
			}
		}

		return $result;
	}

	public function getUsedSources()
	{
		$sourceFilter = $this->getSourceFilter();
		$usedSources = $this->getFilterUsedSources($sourceFilter);

		return array_keys($usedSources);
	}

	/**
	 * @param $sourceFilter
	 *
	 * @return array
	 */
	protected function getFilterUsedSources($sourceFilter)
	{
		$result = [];

		foreach ($sourceFilter as $sourceName => $filter)
		{
			if ($sourceName === 'LOGIC')
			{
				// nothing
			}
			else if (is_numeric($sourceName))
			{
				$result += $this->getFilterUsedSources($filter);
			}
			else
			{
				$result[$sourceName] = true;
			}
		}

		return $result;
	}

	/**
	 * @return array
	 */
	public function getContext($isOnlySelf = false)
	{
		$result = [
			'FILTER_ID' => $this->getId()
		];

		// sales notes

		$salesNotes = $this->getSalesNotes();

		if ($salesNotes !== '')
		{
			$result['SALES_NOTES'] = $salesNotes;
		}

		// delivery options

		$deliveryOptions = $this->getDeliveryOptions();

		if (!empty($deliveryOptions))
		{
			$result['DELIVERY_OPTIONS'] = $deliveryOptions;
		}

		if (!$isOnlySelf)
		{
			$result = $this->mergeParentContext($result);
		}

		return $result;
	}

	protected function mergeParentContext($selfContext)
	{
		$collection = $this->getCollection();
		$iblockLink = $collection ? $collection->getParent() : null;
		$iblockLinkContext = $iblockLink ? $iblockLink->getContext() : null;
		$result = $selfContext;

		if (isset($iblockLinkContext))
		{
			$result += $iblockLinkContext;
		}

		return $result;
	}

	public function getDeliveryOptions()
	{
		$deliveryCollection = $this->getDeliveryCollection();

		return $deliveryCollection->getDeliveryOptions();
	}

	public function getSalesNotes()
	{
		return trim($this->getField('SALES_NOTES'));
	}

	/**
	 * @return \Yandex\Market\Export\FilterCondition\Collection
	 */
	public function getConditionCollection()
	{
		return $this->getChildCollection('FILTER_CONDITION');
	}

	/**
	 * @return \Yandex\Market\Export\Param\Collection
	 */
	public function getDeliveryCollection()
	{
		return $this->getChildCollection('DELIVERY');
	}

	protected function getChildCollectionReference($fieldKey)
	{
		$result = null;

		switch ($fieldKey)
		{
			case 'FILTER_CONDITION':
				$result = Market\Export\FilterCondition\Collection::getClassName();
			break;

			case 'DELIVERY':
				$result = Market\Export\Delivery\Collection::getClassName();
			break;
		}

		return $result;
	}
}
