<?php

namespace Yandex\Market\Export\Entity\Iblock\Offer\Field;

use Yandex\Market;
use Bitrix\Main;

Main\Localization\Loc::loadMessages(__FILE__);

class Source extends Market\Export\Entity\Iblock\Element\Field\Source
{
	public function getQuerySelect($select)
	{
		$entityType = $this->getQueryEntityType();
		$parentSelect = $select;

		if (in_array('CANONICAL_PAGE_URL', $select, true)) // request PRODUCT_URL template
		{
			$parentSelect[] = 'DETAIL_PAGE_URL';
		}

		return [
			'ELEMENT' => $parentSelect,
			$entityType => $select,
		];
	}

	public function getQueryFilter($filter, $select)
	{
		$offersFilter = $filter;
		$distinctFilter = [];

		foreach ($filter as $filterItemKey => $filterItem)
		{
			if ($filterItem['FIELD'] === 'DISTINCT')
			{
				$distinctVariants = $this->getDistinctVariants();
				$distinctFilter[] = $distinctVariants[$filterItem['VALUE']];

				unset($offersFilter[$filterItemKey]);
			}
		}

		$result = parent::getQueryFilter($offersFilter, $select);

		if (!empty($distinctFilter))
		{
			$result['DISTINCT'] = $distinctFilter;
		}

		return $result;
	}

	protected function getQueryEntityType()
	{
		return 'OFFERS';
	}

	public function getOrder()
	{
		return 110;
	}

	public function getElementListValues($elementList, $parentList, $select, $queryContext, $sourceValues)
	{
		$elementSourceType = Market\Export\Entity\Manager::TYPE_IBLOCK_ELEMENT_FIELD;
		$result = [];

		$this->preloadFieldValues($elementList, $select, $queryContext);

		foreach ($elementList as $elementId => $element)
		{
			$parent = null;

			if (isset($parentList[$element['PARENT_ID']]))
			{
				$parent = $parentList[$element['PARENT_ID']];

				if (isset($sourceValues[$elementId][$elementSourceType]))
				{
					$parent = $this->mergeParentSourceValues(
						$parent,
						$sourceValues[$elementId][$elementSourceType]
					);
				}
			}

			$result[$elementId] = $this->getFieldValues($element, $select, $parent, $queryContext); // extract for all
		}

		return $result;
	}

	protected function mergeParentSourceValues($parent, $values)
	{
		foreach ($values as $key => $value)
		{
			if (isset($parent[$key]))
			{
				$parent['~' . $key] = $parent[$key];
			}

			$parent[$key] = $value;
		}

		return $parent;
	}

	public function getFields(array $context = [])
	{
		if (isset($context['OFFER_IBLOCK_ID']))
		{
			$result = parent::getFields($context);
			$langPrefix = $this->getLangPrefix();

			$result[] = [
				'ID' => 'DISTINCT',
				'TYPE' => Market\Export\Entity\Data::TYPE_DISTINCT,
				'VALUE' => Market\Config::getLang($langPrefix . 'FIELD_DISTINCT'),
				'FILTERABLE' => true,
				'SELECTABLE' => false,
				'AUTOCOMPLETE' => false,
			];
		}
		else
		{
			$result = [];
		}

		return $result;
	}

	public function getFieldEnum($field, array $context = [])
	{
		if ($field['ID'] === 'DISTINCT')
		{
			$result = [];
			$langPrefix = $this->getLangPrefix();

			foreach ($this->getDistinctVariants() as $variantKey => $variant)
			{
				$result[] = [
					'ID' => $variantKey,
					'VALUE' => Market\Config::getLang($langPrefix . 'FIELD_DISTINCT_ENUM_' . $variantKey),
				];
			}
		}
		else
		{
			$result = parent::getFieldEnum($field, $context);
		}

		return $result;
	}

	protected function getContextIblockId(array $context = [])
    {
        return isset($context['OFFER_IBLOCK_ID']) ? (int)$context['OFFER_IBLOCK_ID'] : null;
    }

    protected function getLangPrefix()
	{
		return 'IBLOCK_OFFER_FIELD_';
	}

	protected function getDistinctVariants()
	{
		return [
			'AVAILABLE' => [
				'TAG' => 'offer',
				'ATTRIBUTE' => 'available',
				'ORDER' => 'desc',
			],
			'PRICE_MIN' => [
				'TAG' => 'price',
				'ORDER' => 'asc',
			],
			'PRICE_MAX' => [
				'TAG' => 'price',
				'ORDER' => 'desc',
			],
			'OLDPRICE_MIN' => [
				'TAG' => 'oldprice',
				'ORDER' => 'asc',
			],
			'OLDPRICE_MAX' => [
				'TAG' => 'oldprice',
				'ORDER' => 'desc',
			],
			'ID_MIN' => [
				'SOURCE' => $this->getType(),
				'FIELD' => 'ID',
				'ORDER' => 'asc',
			],
			'ID_MAX' => [
				'SOURCE' => $this->getType(),
				'FIELD' => 'ID',
				'ORDER' => 'desc',
			],
		];
	}
}