<?php
namespace Yandex\Market\Export\Routine\QueryBuilder;

use Yandex\Market\Config;
use Yandex\Market\Data\TextString;
use Yandex\Market\Export;

class Filter
{
	public function boot(array $sourceFilter, array &$context)
	{
		return $this->initializeFilterContext($sourceFilter, $context);
	}

	public function compile(array $sourceFilter, array $sourceSelect, array $context, array $changesFilter = null)
	{
		$queryFilter = $this->convertSourceFilterToQuery($sourceFilter, $sourceSelect);

		if ($this->isNeedSplitQueryFilter($queryFilter, $context))
		{
			$result = [
				$this->buildQueryFilter($queryFilter, $context, $changesFilter, 'ELEMENT'),
				$this->buildQueryFilter($queryFilter, $context, $changesFilter, 'OFFERS'),
			];
		}
		else
		{
			$result = [
				$this->buildQueryFilter($queryFilter, $context, $changesFilter),
			];
		}

		return $result;
	}

	public function release(array $sourceFilter, array &$context)
	{
		$this->releaseFilterContext($sourceFilter, $context);
	}

	protected function initializeFilterContext(array $sourceFilter, array &$context)
	{
		foreach ($sourceFilter as $sourceType => $sourceFields)
		{
			if (is_numeric($sourceType)) { continue; } // no support for logic filters

			$source = Export\Entity\Manager::getSource($sourceType);
			$source->initializeFilterContext($sourceFields, $context, $sourceFilter);
		}

		return $sourceFilter;
	}

	protected function releaseFilterContext(array $sourceFilter, array $context)
	{
		foreach ($sourceFilter as $sourceType => $sourceFields)
		{
			if (is_numeric($sourceType)) { continue; } // no support for logic filters

			$source = Export\Entity\Manager::getSource($sourceType);
			$source->releaseFilterContext($sourceFields, $context, $sourceFilter);
		}
	}

	protected function convertSourceFilterToQuery(array $sourceFilterList, array $sourceSelectList, $isChild = false)
	{
		$result = [];
		$logic = null;

		foreach ($sourceFilterList as $sourceName => $sourceFilter)
		{
			$queryFilter = null;

			if ($sourceName === 'LOGIC')
			{
				$logic = (string)$sourceFilter;
			}
			else if (is_numeric($sourceName))
			{
				$queryFilter = $this->convertSourceFilterToQuery($sourceFilter, $sourceSelectList, true);
			}
			else
			{
				$source = Export\Entity\Manager::getSource($sourceName);

				if ($source->isFilterable())
				{
					$sourceSelect = isset($sourceSelectList[$sourceName]) ? $sourceSelectList[$sourceName] : [];

					$queryFilter = $source->getQueryFilter($sourceFilter, $sourceSelect);
				}
			}

			if ($queryFilter !== null)
			{
				foreach ($queryFilter as $chainType => $filter)
				{
					if (!empty($filter))
					{
						if (isset($result[$chainType]))
						{
							// nothing
						}
						else if ($isChild)
						{
							$result[$chainType] = ($logic !== null ? [ 'LOGIC' => $logic ] : []);
						}
						else
						{
							$result[$chainType] = [];
						}

						$result[$chainType][] = $filter;
					}
				}
			}
		}

		return $result;
	}

	/** Нужно ли разбить запрос по товарам и предложениям отдельно */
	protected function isNeedSplitQueryFilter(array $queryFilter, array $context)
	{
		return (
			!empty($queryFilter['CATALOG'])
			&& empty($queryFilter['OFFERS'])
			&& $context['HAS_OFFER']
			&& !$context['OFFER_ONLY']
			&& !$this->canQueryCatalogFilterMerge($queryFilter['CATALOG'])
			&& !Export\Entity\Catalog\Provider::isCatalogTypeCompatibility($context)
		);
	}

	/** Можно ли фильтровать по данным каталога без subfilter (пример, =AVAILABLE => Y) */
	protected function canQueryCatalogFilterMerge($catalogFilter)
	{
		$result = false;

		if (is_array($catalogFilter) && Export\Entity\Catalog\Provider::useSkuAvailableCalculation())
		{
			$result = true;
			$availableFieldName = Export\Entity\Catalog\Provider::useCatalogShortFields()
				? '=AVAILABLE'
				: '=CATALOG_AVAILABLE';

			foreach ($catalogFilter as $partName => $part)
			{
				$canMerge = false;

				if (!is_array($part))
				{
					$canMerge = ($partName === $availableFieldName && $part === 'Y');
				}
				else if (count($part) === 1)
				{
					$canMerge = (isset($part[$availableFieldName]) && $part[$availableFieldName] === 'Y');
				}

				if (!$canMerge)
				{
					$result = false;
					break;
				}
			}
		}

		return $result;
	}

	/**
	 * Формируем фильтры для запросов
	 *
	 * @param $queryFilter array
	 * @param $queryContext array
	 * @param $changesFilter array|null
	 * @param $catalogBehavior string|null
	 *
	 * @return array
	 */
	protected function buildQueryFilter($queryFilter, $queryContext, $changesFilter = null, $catalogBehavior = null)
	{
		$isOfferSubQueryInitialized = false;
		$result = $queryFilter + [
			'ELEMENT' => [],
			'OFFERS' => [],
		];

		// catalog filter

		if (!empty($result['CATALOG']))
		{
			if (!$queryContext['HAS_OFFER']) // hasn't offers
			{
				$result['ELEMENT'][] = $result['CATALOG'];
			}
			else if (!empty($result['OFFERS'])) // has required offers
			{
				$result['OFFERS'][] = $result['CATALOG'];
			}
			else if (!empty($queryContext['OFFER_ONLY']))
			{
				$result['OFFERS'][] = $result['CATALOG'];
			}
			else if ($this->canQueryCatalogFilterMerge($result['CATALOG']))
			{
				$isOfferSubQueryInitialized = true;
				$result['ELEMENT'][] = $result['CATALOG'];
				$result['OFFERS'][] = $result['CATALOG'];
			}
			else if ($catalogBehavior === 'ELEMENT')
			{
				$typeField = Export\Entity\Catalog\Provider::useCatalogShortFields() ? 'TYPE' : 'CATALOG_TYPE';

				$result['ELEMENT']['!=' . $typeField] = Export\Run\Steps\Offer::ELEMENT_TYPE_SKU;
				$result['ELEMENT'][] = $result['CATALOG'];
			}
			else if ($catalogBehavior === 'OFFERS')
			{
				$result['OFFERS'][] = $result['CATALOG'];
			}
			else
			{
				// element match catalog condition or has offers match condition

				$isOfferSubQueryInitialized = true;
				$catalogOfferFilter = $this->filterDefaults('OFFERS', $queryContext['OFFER_IBLOCK_ID'], $queryContext);
				$catalogOfferFilter[] = $result['CATALOG'];

				$result['ELEMENT'][] = [
					'LOGIC' => 'OR',
					$result['CATALOG'],
					[
						'ID' => \CIBlockElement::SubQuery(
							'PROPERTY_' . $queryContext['OFFER_PROPERTY_ID'],
							$catalogOfferFilter
						),
					]
				];

				// filter offers by catalog rules

				$result['OFFERS'][] = $result['CATALOG'];
			}
		}

		// offer subquery for elements

		if ($queryContext['HAS_OFFER'] && !empty($result['OFFERS']) && empty($result['DISTINCT']) && empty($result['ELEMENT']))
		{
			$result['DIRECTION'] = 'OFFER';
		}
		else
		{
			$result['DIRECTION'] = 'ELEMENT';
		}

		// extend by changes filter

		if (!empty($changesFilter))
		{
			$result = $this->appendQueryFilterChanges($result, $changesFilter, $queryContext);
		}

		// init defaults

		$result['ELEMENT'] = array_merge(
			$this->filterDefaults('ELEMENT', $queryContext['IBLOCK_ID'], $queryContext),
			$result['ELEMENT']
		);

		if ($queryContext['HAS_OFFER'])
		{
			$hasOfferUserFilter = !empty($result['OFFERS']);

			$result['OFFERS'] = array_merge(
				$this->filterDefaults('OFFERS', $queryContext['OFFER_IBLOCK_ID'], $queryContext),
				$result['OFFERS']
			);

			if (
				$hasOfferUserFilter
				&& !$isOfferSubQueryInitialized
				&& $result['DIRECTION'] !== 'OFFER'
				&& !$this->isIgnoreOfferSubQuery($queryContext)
			)
			{
				$result['ELEMENT'][] = [
					'ID' => \CIBlockElement::SubQuery(
						'PROPERTY_' . $queryContext['OFFER_PROPERTY_ID'],
						$result['OFFERS']
					),
				];
			}
		}

		// distinct

		if (!empty($result['DISTINCT']))
		{
			$result['DISTINCT'] = call_user_func_array('array_merge', $result['DISTINCT']);
		}

		$result = $this->prioritizeQueryFilters($result);

		return $result;
	}

	protected function filterDefaults($entityType, $iblockId, array $context)
	{
		$result = [
			'IBLOCK_ID' => $iblockId,
		];

		if (empty($context['FILTER_MANUAL'][$entityType]['ACTIVE']))
		{
			$result += [
				'ACTIVE' => 'Y',
				'ACTIVE_DATE' => 'Y',
			];
		}

		return $result;
	}

	protected function appendQueryFilterChanges(array $resultFilter, array $changesFilter, array $queryContext)
	{
		foreach ($changesFilter as $entityType => $entityFilter)
		{
			// modify by direction

			if ($entityType === 'ELEMENT' && $resultFilter['DIRECTION'] === 'OFFER')
			{
				$entityOperations = $this->extractFilterOperations($entityFilter);

				if (count($entityOperations) === 1 && $entityOperations[0]['FIELD'] === 'ID')
				{
					$skuPropertyCompare = ($entityOperations[0]['PREFIX'] !== '' ? $entityOperations[0]['PREFIX'] : '=');
					$skuPropertyKey = 'PROPERTY_' . $queryContext['OFFER_PROPERTY_ID'];

					$entityType = 'OFFERS';
					$entityFilter = [
						$skuPropertyCompare . $skuPropertyKey => $entityOperations[0]['VALUE'],
					];
				}
				else
				{
					$resultFilter['DIRECTION'] = 'ELEMENT';
				}
			}

			// push filter

			if (!isset($resultFilter[$entityType]))
			{
				$resultFilter[$entityType] = [];
			}

			$resultFilter[$entityType][] = $entityFilter;
		}

		return $resultFilter;
	}

	/**
	 * Список операций, которые используются в фильтре
	 *
	 * @param $filter array
	 *
	 * @return array{FIELD: string, PREFIX: string, OPERATION: string, VALUE: mixed}[]
	 */
	protected function extractFilterOperations($filter)
	{
		$result = [];

		if (is_array($filter))
		{
			foreach ($filter as $fieldName => $filterValue)
			{
				if (is_numeric($fieldName))
				{
					$operations = $this->extractFilterOperations($filterValue);

					foreach ($operations as $operation)
					{
						$result[] = $operation;
					}
				}
				else
				{
					$operation = \CIBlock::MkOperationFilter($fieldName);
					$operation['VALUE'] = $filterValue;

					if (!isset($operation['PREFIX']))
					{
						$fieldNameLength = TextString::getLength($fieldName);
						$operationFieldLength = TextString::getLength($operation['FIELD']);
						$operationPrefixLength = $fieldNameLength - $operationFieldLength;

						$operation['PREFIX'] = TextString::getSubstring($fieldName, 0, $operationPrefixLength);
					}

					$result[] = $operation;
				}
			}
		}

		return $result;
	}

	/** Обрабатывать все элементы, вне зависимости от фильтра по торговым предложениям */
	protected function isIgnoreOfferSubQuery(array $context)
	{
		return (
			!empty($context['CAN_IGNORE_OFFER_SUBQUERY'])
			&& Config::getOption('export_offer_process_all_elements', 'N') === 'Y'
			&& !Export\Entity\Catalog\Provider::isCatalogTypeCompatibility($context)
		);
	}

	/** Приоритизация для группы запросов */
	protected function prioritizeQueryFilters($filter)
	{
		$keys = [
			'ELEMENT',
			'OFFERS',
		];

		foreach ($keys as $key)
		{
			if (!isset($filter[$key])) { continue; }

			$filter[$key] = $this->prioritizeFilter($filter[$key]);
		}

		return $filter;
	}

	/** Переносим базовые параметры запроса в корень для упрощения запроса */
	protected function prioritizeFilter($filter)
	{
		$priorityMap = [
			'IBLOCK_ID' => 0,
			'ACTIVE' => 1,
			'ACTIVE_DATE' => 1,
			'SUBSECTION' => 2,
			'SECTION_ID' => 2,
			'SECTION_ACTIVE' => 2,
			'SECTION_GLOBAL_ACTIVE' => 2,
			'SECTION_SCOPE' => 2,
			'IBLOCK_SECTION_ID' => 2,
			'INCLUDE_SUBSECTIONS' => 2,
			'CHECK_PERMISSIONS' => 3,
			'PERMISSIONS_BY' => 3,
			'MIN_PERMISSION' => 3,
		];
		$priorityChain = [];
		$hasConflicts = false;
		$result = $filter;

		foreach ($filter as $firstKey => $firstLevel)
		{
			$isLevelVirtual = false;

			if (!is_numeric($firstKey))
			{
				$isLevelVirtual = true;
				$firstLevel = [
					$firstKey => $firstLevel,
				];
			}

			if (!is_array($firstLevel) || (isset($firstLevel['LOGIC']) && $firstLevel['LOGIC'] !== 'AND')) { continue; }

			foreach ($firstLevel as $secondKey => $secondLevel)
			{
				$operation = \CIBlock::MkOperationFilter($secondKey);

				if (!isset($priorityMap[$operation['FIELD']])) { continue; }

				$priority = $priorityMap[$operation['FIELD']];

				if (isset($priorityChain[$priority][$secondKey]))
				{
					$hasConflicts = true;
					break;
				}

				if (!isset($priorityChain[$priority])) { $priorityChain[$priority] = []; }

				$priorityChain[$priority][$secondKey] = $secondLevel;

				if (!$isLevelVirtual)
				{
					unset($result[$firstKey][$secondKey]);

					/** @noinspection PhpConditionAlreadyCheckedInspection */
					if (empty($result[$firstKey]))
					{
						/** @noinspection PhpConditionAlreadyCheckedInspection */
						unset($result[$firstKey]);
					}
				}
			}

			if ($hasConflicts) { break; }
		}

		if ($hasConflicts)
		{
			$result = $filter;
		}
		else if (!empty($priorityChain))
		{
			ksort($priorityChain);

			$priorityFilter = array_merge(...$priorityChain);

			$result = array_merge($priorityFilter, $result);
		}

		return $result;
	}
}