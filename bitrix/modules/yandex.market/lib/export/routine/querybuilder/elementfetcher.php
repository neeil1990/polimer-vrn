<?php
namespace Yandex\Market\Export\Routine\QueryBuilder;

use Yandex\Market\Config;
use Yandex\Market\Export;

class ElementFetcher
{
	protected $excludeDataClass;
	protected $excludeFilter;
	protected $excludeElementField;
	protected $excludeParentField;
	protected $includeDataClass;
	protected $includeFilter;
	protected $includeElementField;
	protected $includeParentField;

	public function exclude($dataClass, array $filter, $elementField, $parentField = null)
	{
		$this->excludeDataClass = $dataClass;
		$this->excludeFilter = $filter;
		$this->excludeElementField = $elementField;
		$this->excludeParentField = $parentField;
	}

	public function included($dataClass, array $filter, $elementField, $parentField = null)
	{
		$this->includeDataClass = $dataClass;
		$this->includeFilter = $filter;
		$this->includeElementField = $elementField;
		$this->includeParentField = $parentField;
	}

	public function load(array $queryFilter, array $querySelect, array $context, $offset = 0)
	{
		if ($queryFilter['DIRECTION'] === 'OFFER')
		{
			$result = $this->loadElementsByOffer($queryFilter, $querySelect, $context, $offset);
		}
		else
		{
			$result = $this->loadElementsBySelf($queryFilter, $querySelect, $context, $offset);
		}

		return $result;
	}

	protected function loadElementsBySelf(array $queryFilter, array $querySelect, array $context, $offset = 0)
	{
		$parentList = [];
		$hasOffers = isset($context['OFFER_PROPERTY_ID']);

		// elements

		$pageSize = $this->pageSize($context);

		$elementFilter = $queryFilter['ELEMENT'];
		$elementFilter = $this->extendQueryElementListFilter($elementFilter, $context);

		$elementSelect = isset($querySelect['ELEMENT']) ? (array)$querySelect['ELEMENT'] : [];
		$elementSelect = $this->extendQueryElementListSelect($elementSelect, $context);

		$elementQueryResult = $this->queryElementList($elementFilter, $elementSelect, $offset, $pageSize);
		$elementList = $elementQueryResult['ELEMENT'];

		// offers

		if ($hasOffers)
		{
			$isCatalogTypeCompatibility = Export\Entity\Catalog\Provider::isCatalogTypeCompatibility($context);
			$foundParents = [];

			if ($isCatalogTypeCompatibility || !empty($context['OFFER_ONLY']))
			{
				$parentList = $elementList;
			}
			else
			{
				foreach ($elementList as $elementId => $element)
				{
					if ($this->getElementCatalogType($element, $context) === Export\Run\Steps\Offer::ELEMENT_TYPE_SKU)
					{
						$parentList[$elementId] = $element;
					}
				}

				$elementList = array_diff_key($elementList, $parentList);
			}

			if (!empty($parentList))
			{
				$offerFilter = $queryFilter['OFFERS'];
				$offerFilter['=PROPERTY_' . $context['OFFER_PROPERTY_ID']] = array_keys($parentList);
				$offerFilter = $this->extendQueryOfferListFilter($offerFilter, $context);

				$offerSelect = isset($querySelect['OFFERS']) ? (array)$querySelect['OFFERS'] : [];
				$offerSelect = $this->extendQueryOfferListSelect($offerSelect);

				$offerQueryResult = $this->queryOfferList($offerFilter, $offerSelect, $context);

				$foundParents = array_column($offerQueryResult['ELEMENT'], 'ID', 'PARENT_ID');

				$offerList = $offerQueryResult['ELEMENT'];
				$offerList = $this->processQueryResultOfferList($offerList, $context);

				$elementList += $offerList;
			}

			foreach ($parentList as $parentId => $parent)
			{
				if (!isset($foundParents[$parentId]))
				{
					unset($parentList[$parentId]);
				}
				else if ($isCatalogTypeCompatibility && isset($elementList[$parentId]))
				{
					unset($elementList[$parentId]);
				}
			}
		}

		return [
			'ELEMENT' => $elementList,
			'PARENT' => $parentList,
			'OFFSET' => $elementQueryResult['OFFSET'],
			'HAS_NEXT' => $elementQueryResult['HAS_NEXT'],
		];
	}

	protected function loadElementsByOffer(array $queryFilter, array $querySelect, array $context, $offset = 0)
	{
		$hasOffers = isset($context['OFFER_PROPERTY_ID']);
		$elementList = [];
		$parentList = [];
		$hasNext = false;

		if ($hasOffers)
		{
			// offers

			$pageSize = $this->pageSize($context, 'OFFER');

			$offerFilter = $queryFilter['OFFERS'];
			$offerFilter = $this->extendQueryOfferListFilter($offerFilter, $context, 'OFFER');

			$offerSelect = isset($querySelect['OFFERS']) ? (array)$querySelect['OFFERS'] : [];
			$offerSelect = $this->extendQueryOfferListSelect($offerSelect);

			$offerQueryResult = $this->queryOfferList($offerFilter, $offerSelect, $context, $offset, $pageSize);

			$elementList = $offerQueryResult['ELEMENT'];
			$elementList = $this->processQueryResultOfferList($elementList, $context, 'OFFER');

			$offset = $offerQueryResult['OFFSET'];
			$hasNext = $offerQueryResult['HAS_NEXT'];

			// parents

			$parentMap = array_column($elementList, 'ID', 'PARENT_ID');

			if (!empty($parentMap))
			{
				$elementFilter = $queryFilter['ELEMENT'];
				$elementFilter['=ID'] = array_keys($parentMap);
				$elementFilter = $this->extendQueryElementListFilter($elementFilter, $context, 'OFFER');

				$elementSelect = isset($querySelect['ELEMENT']) ? (array)$querySelect['ELEMENT'] : [];
				$elementSelect = $this->extendQueryElementListSelect($elementSelect, $context, 'OFFER');

				$elementQueryResult = $this->queryElementList($elementFilter, $elementSelect);
				$parentList = $elementQueryResult['ELEMENT'];
			}

			// unset elements without parent

			$elementList = array_filter($elementList, static function($element) use ($parentList) {
				return isset($parentList[$element['PARENT_ID']]);
			});
		}

		return [
			'ELEMENT' => $elementList,
			'PARENT' => $parentList,
			'OFFSET' => $offset,
			'HAS_NEXT' => $hasNext,
		];
	}

	protected function pageSize(array $context, $direction = 'ELEMENT')
	{
		$option = (int)Config::getOption('export_run_offer_page_size');

		if ($option > 0)
		{
			$result = $option;
		}
		else if ($direction === 'OFFER' || !$context['HAS_OFFER'])
		{
			$result = 100;
		}
		else
		{
			$result = 50;
		}

		return $result;
	}

	protected function extendQueryElementListFilter(array $filter, array $context, $direction = 'ELEMENT')
	{
		if ($this->includeDataClass !== null && $direction !== 'OFFER')
		{
			if (!$context['HAS_OFFER'] || $this->includeParentField === null)
			{
				$filter[] = [
					'ID' => $this->compileIncludeFilter(),
				];
			}
			else if (!empty($context['OFFER_ONLY']))
			{
				$filter[] = [
					'ID' => $this->compileIncludeFilter($this->includeParentField),
				];
			}
			else
			{
				$filter[] = [
					'LOGIC' => 'OR',
					[ 'ID' => $this->compileIncludeFilter() ],
					[ 'ID' => $this->compileIncludeFilter($this->includeParentField) ],
				];
			}
		}

		if ($this->excludeDataClass !== null)
		{
			if ($direction !== 'OFFER')
			{
				$filter[] = [
					'!ID' => $this->compileExcludeFilter(),
				];
			}

			if ($context['HAS_OFFER'] && !empty($context['USE_DISTINCT']) && $this->excludeParentField !== null)
			{
				$filter[] = [
					'!ID' => $this->compileExcludeFilter($this->excludeParentField),
				];
			}
		}

		return $filter;
	}

	protected function extendQueryElementListSelect(array $select, array $context, $direction = 'ELEMENT')
	{
		$select = array_merge([ 'IBLOCK_ID', 'ID' ], $select);

		if (
			$direction !== 'OFFER'
			&& $context['HAS_OFFER']
			&& empty($context['OFFER_ONLY'])
			&& !Export\Entity\Catalog\Provider::isCatalogTypeCompatibility($context)
		)
		{
			$select[] = Export\Entity\Catalog\Provider::useCatalogShortFields() ? 'TYPE' : 'CATALOG_TYPE';
		}

		return array_unique($select);
	}

	protected function extendQueryOfferListFilter(array $filter, array $context, $direction = 'ELEMENT')
	{
		if ($this->isDelayedExcludeQueryOffers($context, $direction)) { return $filter; }

		if ($this->includeDataClass !== null)
		{
			$filter[] = [
				'ID' => $this->compileIncludeFilter(),
			];
		}

		if ($this->excludeDataClass !== null)
		{
			$filter[] = [
				'!ID' => $this->compileExcludeFilter(),
			];
		}

		return $filter;
	}

	protected function extendQueryOfferListSelect(array $select)
	{
		$select = array_merge([ 'IBLOCK_ID', 'ID' ], $select);
		$select = array_unique($select);

		return $select;
	}

	protected function processQueryResultOfferList($offerList, $context, $direction = 'ELEMENT')
	{
		if (!$this->isDelayedExcludeQueryOffers($context, $direction)) { return $offerList; }

		if ($this->includeDataClass !== null)
		{
			$offerList = $this->filterExcludeOffers(
				$offerList,
				$this->includeDataClass,
				$this->includeFilter,
				$this->includeElementField,
				true
			);
		}

		if ($this->excludeDataClass !== null)
		{
			$offerList = $this->filterExcludeOffers(
				$offerList,
				$this->excludeDataClass,
				$this->excludeFilter,
				$this->excludeElementField
			);
		}

		return $offerList;
	}

	protected function filterExcludeOffers(array $offerList, $dataClass, array $filter, $elementField, $inverse = false)
	{
		$offerIds = array_keys($offerList);

		foreach (array_chunk($offerIds, 500) as $offerIdsChunk)
		{
			$filter['=' . $elementField] = $offerIdsChunk;

			$queryReadyOffers = $dataClass::getList([
				'filter' => $filter,
				'select' => [ $elementField ],
			]);

			while ($readyOffer = $queryReadyOffers->fetch())
			{
				$elementId = $readyOffer[$elementField];

				if (isset($offerList[$elementId]) !== $inverse)
				{
					unset($offerList[$elementId]);
				}
			}
		}

		return $offerList;
	}

	protected function isDelayedExcludeQueryOffers(array $context, $direction)
	{
		return (
			$direction !== 'OFFER'
			&& Export\Entity\Catalog\Provider::isCatalogTypeCompatibility($context)
		);
	}

	protected function compileExcludeFilter($field = null)
	{
		if ($field === null) { $field = $this->excludeElementField; }

		return new Export\Run\Helper\ExcludeFilter(
			$this->excludeDataClass,
			$field,
			$this->excludeFilter
		);
	}

	protected function compileIncludeFilter($field = null)
	{
		if ($field === null) { $field = $this->includeElementField; }

		return new Export\Run\Helper\ExcludeFilter(
			$this->includeDataClass,
			$field,
			$this->includeFilter
		);
	}

	protected function queryElementList($filter, $select, $offset = 0, $pageSize = null)
	{
		$elementList = [];
		$count = 0;

		if ($offset > 0)
		{
			$filter[] = [ '>ID' => $offset ];
		}

		if ($pageSize > 0)
		{
			$paging = [ 'nTopCount' => $pageSize ];
		}
		else
		{
			$paging = false;
		}

		$select[] = 'ID';

		$queryElementList = \CIBlockElement::GetList(
			[ 'ID' => 'ASC' ],
			$filter,
			false,
			$paging,
			$select
		);

		while ($element = $queryElementList->Fetch())
		{
			$elementList[$element['ID']] = $element;

			$offset = (int)$element['ID'];
			++$count;
		}

		return [
			'ELEMENT' => $elementList,
			'OFFSET' => $offset,
			'HAS_NEXT' => ($pageSize > 0 && $count >= $pageSize),
		];
	}

	protected function queryOfferList(array $filter, array $select, array $context, $offset = 0, $pageSize = null)
	{
		$offerList = [];
		$count = 0;

		$skuPropertyKey = 'PROPERTY_' . $context['OFFER_PROPERTY_ID'];
		$skuPropertyValueKey = $skuPropertyKey . '_VALUE';

		if ($offset > 0)
		{
			$filter[] = [ '>ID' => $offset ];
		}

		if ($pageSize > 0)
		{
			$paging = [ 'nTopCount' => $pageSize ];
		}
		else
		{
			$paging = false;
		}

		$select[] = $skuPropertyKey;
		$select[] = 'ID';

		$queryOfferList = \CIBlockElement::GetList(
			[ 'ID' => 'ASC' ],
			$filter,
			false,
			$paging,
			$select
		);

		while ($offer = $queryOfferList->Fetch())
		{
			$offerElementId = (int)$offer[$skuPropertyValueKey];

			if ($offerElementId > 0)
			{
				$offer['PARENT_ID'] = $offerElementId;

				$offerList[$offer['ID']] = $offer;
			}

			$offset = (int)$offer['ID'];
			++$count;
		}

		return [
			'ELEMENT' => $offerList,
			'OFFSET' => $offset,
			'HAS_NEXT' => ($pageSize > 0 && $count >= $pageSize),
		];
	}

	protected function getElementCatalogType(array $element, array $context)
	{
		$result = Export\Run\Steps\Offer::ELEMENT_TYPE_PRODUCT;

		if (!empty($context['OFFER_ONLY']))
		{
			$result = Export\Run\Steps\Offer::ELEMENT_TYPE_SKU;
		}
		else if (isset($element['TYPE']))
		{
			$result = (int)$element['TYPE'];
		}
		else if (isset($element['CATALOG_TYPE']))
		{
			$result = (int)$element['CATALOG_TYPE'];
		}
		else if (
			(array_key_exists('CATALOG_TYPE', $element) || array_key_exists('TYPE', $element))
			&& !empty($context['OFFER_IBLOCK_ID'])
		)
		{
			$result = Export\Run\Steps\Offer::ELEMENT_TYPE_SKU;
		}

		return $result;
	}
}