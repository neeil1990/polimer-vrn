<?php

namespace Yandex\Market\Export\Entity\Iblock\Element\Property;

use Bitrix\Highloadblock;
use Yandex\Market;
use Bitrix\Main;
use Bitrix\Iblock;
use Bitrix\Catalog;

Main\Localization\Loc::loadMessages(__FILE__);

class Source extends Market\Export\Entity\Reference\Source
{
	protected $highloadDataClassCache = [];
	protected $specialTypes = [
		'directory' => true,
		'SKU' => true,
		'HTML' => true,
		'ElementXmlID' => true,
	];

	public function isFilterable()
	{
		return true;
	}

	public function getQueryFilter($filter, $select)
	{
		return [
			'ELEMENT' => $this->buildQueryFilter($filter)
		];
	}

	public function getOrder()
	{
		return 200;
	}

	public function getElementListValues($elementList, $parentList, $select, $queryContext, $sourceValues)
	{
		$result = [];
		$parentToElementMapByIblock = [];

		foreach ($elementList as $elementId => $element)
		{
			$parent = null;

			if (!isset($element['PARENT_ID'])) // is not offer
			{
				$parent = $element;
			}
			else if (isset($parentList[$element['PARENT_ID']])) // has parent
			{
				$parent = $parentList[$element['PARENT_ID']];
			}

			if (isset($parent))
			{
				if (!isset($parentToElementMapByIblock[$parent['IBLOCK_ID']]))
				{
					$parentToElementMapByIblock[$parent['IBLOCK_ID']] = [];
				}

				if (!isset($parentToElementMapByIblock[$parent['IBLOCK_ID']][$parent['ID']]))
				{
					$parentToElementMapByIblock[$parent['IBLOCK_ID']][$parent['ID']] = [];
				}

				$parentToElementMapByIblock[$parent['IBLOCK_ID']][$parent['ID']][] = $elementId;
			}
		}

		if (!empty($parentToElementMapByIblock))
		{
			foreach ($parentToElementMapByIblock as $iblockId => $parentToElementMap)
			{
				$parentIds = array_keys($parentToElementMap);
				$propertyValuesList = $this->getPropertyValues($iblockId, $parentIds, $select, $queryContext);

				foreach ($propertyValuesList as $parentId => $propertyValues)
				{
					if (isset($parentToElementMap[$parentId]))
					{
						foreach ($parentToElementMap[$parentId] as $elementId)
						{
							$result[$elementId] = $propertyValues;
						}
					}
				}
			}
		}

		return $result;
	}

	public function getFields(array $context = [])
	{
		$iblockId = $this->getContextIblockId($context);

		return $this->getPropertyFields($iblockId);
	}

	public function suggestFields($query, array $context = [])
	{
		$iblockId = (int)$this->getContextIblockId($context);

		if ($iblockId <= 0) { return []; }

		$queryFilter = [
			'LOGIC' => 'OR',
			[ '%CODE' => $query ],
			[ '%NAME' => $query ],
		];

		if (is_numeric($query))
		{
			$queryFilter[] = [ '=ID' => $query ];
		}

		return $this->loadPropertyFields([
			'=IBLOCK_ID' => $iblockId,
			$queryFilter,
		], 50);
	}

	protected function getContextIblockId(array $context)
	{
		return isset($context['IBLOCK_ID']) ? $context['IBLOCK_ID'] : null;
	}

	public function getPropertyFields($iblockId)
	{
		$iblockId = (int)$iblockId;

		if ($iblockId > 0)
		{
			$result = $this->loadPropertyFields([ '=IBLOCK_ID' => $iblockId ]);
		}
		else
		{
			$result = [];
		}

		return $result;
	}

	protected function loadPropertyData($propertyIds, array $select)
	{
		$result = [];

		Main\Type\Collection::normalizeArrayValuesByInt($propertyIds);

		if (empty($propertyIds)) { return $result; }

		$selectMap = array_flip($select);
		$propertyFields = $this->loadPropertyFields([ '=ID' => $propertyIds ]);

		foreach ($propertyFields as $propertyField)
		{
			$result[$propertyField['ID']] = array_intersect_key($propertyField, $selectMap);
		}

		return $result;
	}

	protected function loadPropertyFields($filter, $limit = null)
	{
		$result = [];

		if (Main\Loader::includeModule('iblock'))
		{
			$langPrefix = $this->getLangPrefix();
			$supportAutocompleteTypes = $this->getSupportAutocompleteTypes();

			$query = Iblock\PropertyTable::getList([
				'filter' => $filter,
				'select' => ['ID', 'NAME', 'PROPERTY_TYPE', 'USER_TYPE', 'USER_TYPE_SETTINGS', 'WITH_DESCRIPTION', 'LINK_IBLOCK_ID', 'MULTIPLE', 'IBLOCK_ID'],
				'limit' => $limit,
			]);

			while ($propertyRow = $query->fetch())
			{
				$propertyType = $this->getPropertyType($propertyRow);
				$dataType = $propertyRow['PROPERTY_TYPE'];
				$linkIblockId = (int)$propertyRow['LINK_IBLOCK_ID'];

				switch ($propertyRow['USER_TYPE'])
				{
					case 'DateTime':
						$dataType = Market\Export\Entity\Data::TYPE_DATETIME;
					break;

					case 'Date':
						$dataType = Market\Export\Entity\Data::TYPE_DATE;
					break;

					case 'ym_service_category':
						$dataType = Market\Export\Entity\Data::TYPE_SERVICE_CATEGORY;
					break;

					case 'directory':
						$dataType = Market\Export\Entity\Data::TYPE_ENUM;
					break;

					case 'ElementXmlID':
						$dataType = Market\Export\Entity\Data::TYPE_IBLOCK_ELEMENT;
					break;
				}

				$propertyData = [
					'VALUE' => '[' . $propertyRow['ID'] . '] ' . $propertyRow['NAME'],
					'PROPERTY_TYPE' => $propertyRow['PROPERTY_TYPE'],
					'USER_TYPE' => $propertyRow['USER_TYPE'],
					'USER_TYPE_SETTINGS' => $propertyRow['USER_TYPE_SETTINGS'] ? unserialize($propertyRow['USER_TYPE_SETTINGS']) : null,
					'MULTIPLE' => $propertyRow['MULTIPLE'] === 'Y',
					'IBLOCK_ID' => $propertyRow['IBLOCK_ID'],
				];

				// export self

				$result[] = [
					'ID' => $propertyRow['ID'],
					'TYPE' => $dataType,
					'FILTERABLE' => true,
					'SELECTABLE' => true,
					'AUTOCOMPLETE' => isset($supportAutocompleteTypes[$propertyType]),
					'LINK_IBLOCK_ID' => $linkIblockId
				] + $propertyData;

				// export inner fields

				foreach ($this->getPropertyInnerFields($propertyRow) as $innerField)
				{
					$innerFieldTitle = Market\Config::getLang($langPrefix . 'FIELD_INNER_' . $innerField, null, $innerField);

					$result[] = [
						'ID' => $propertyRow['ID'] . '.' . $innerField,
						'VALUE' => sprintf('%s (%s)', $propertyData['VALUE'], $innerFieldTitle),
						'TYPE' => Market\Export\Entity\Data::TYPE_STRING,
						'FILTERABLE' => false,
						'SELECTABLE' => true,
					] + $propertyData;
				}
			}
		}

		return $result;
	}

	protected function getPropertyInnerFields($property)
	{
		$propertyType = $this->getPropertyType($property);
		$result = $this->getPropertyTypeInnerFields($propertyType);

		if ($property['WITH_DESCRIPTION'] === 'Y')
		{
			array_unshift($result, 'DESCRIPTION');
		}

		return $result;
	}

	protected function getPropertyTypeInnerFields($propertyType)
	{
		if ($propertyType === Market\Export\Entity\Data::TYPE_ENUM)
		{
			$result = [
				'VALUE_XML_ID',
			];
		}
		else if ($propertyType === Market\Export\Entity\Data::TYPE_IBLOCK_SECTION)
		{
			$result = [
				'NAME',
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
		$result = null;

		$propertyType = $this->getPropertyType($field);
		$limit = 50;

		switch ($propertyType)
		{
			case 'L':
			case 'directory':
				$dbQuery = $this->getAutocompleteQuery($field, [], $limit + 1);

				$result = $this->getAutocompleteResultItems($field, $dbQuery);
			break;
		}

		if ($result === null)
		{
			$result = parent::getFieldEnum($field, $context);
		}
		else if (!empty($field['AUTOCOMPLETE']) && count($result) > $limit)
		{
			$result = null; // use autocomplete
		}

		return $result;
	}

	protected function hasFewPropertyVariants(array $field, $excludeVariants = null)
	{
		$excludeCount = is_array($excludeVariants) ? count($excludeVariants) : 1;
		$query = $this->getAutocompleteQuery($field, [], $excludeCount + 1);

		if ($query !== null)
		{
			$variantsCount = $this->getAutocompleteResultCount($field, $query);
			$result = ($variantsCount > $excludeCount);
		}
		else
		{
			$result = true;
		}

		return $result;
	}

	public function getFieldAutocomplete($field, $query, array $context = [])
	{
		$filter = $this->getAutocompleteFilter($field, $query);
		$dbQuery = $this->getAutocompleteQuery($field, $filter);
		$result = $this->getAutocompleteResultItems($field, $dbQuery);

		if (
			$this->isAutocompleteQueryMatchType($field['TYPE'], $query)
			&& !$this->isAutocompleteResultContainsQuery($result, $query)
		)
		{
			$result[] = [
				'ID' => $query,
				'VALUE' => $query,
			];
		}

		return $result;
	}

	public function getFieldDisplayValue($field, $valueList, array $context = [])
	{
		$filter = $this->getDisplayValueFilter($field, $valueList);
		$dbQuery = $this->getAutocompleteQuery($field, $filter, count($valueList));

		return $this->getAutocompleteResultItems($field, $dbQuery);
	}

	/**
	 * @param array $field
	 * @param \CDBResult|Main\DB\Result|null $dbQuery
	 * @return int
	 */
	protected function getAutocompleteResultCount($field, $dbQuery)
	{
		if ($dbQuery === null)
		{
			$result = 0;
		}
		else if ($dbQuery instanceof \CDBResult)
		{
			$result = $dbQuery->SelectedRowsCount();
		}
		else if ($dbQuery instanceof Main\DB\Result)
		{
			$result = $dbQuery->getSelectedRowsCount();
		}
		else
		{
			$items = $this->getAutocompleteResultItems($field, $dbQuery);
			$result = count($items);
		}

		return $result;
	}

	/**
	 * @param array $field
	 * @param \CDBResult|Main\DB\Result|null $dbQuery
	 * @return array
	 */
	protected function getAutocompleteResultItems($field, $dbQuery)
	{
		$propertyType = $this->getPropertyType($field);
		$result = [];

		if ($dbQuery !== null)
		{
			while ($row = $dbQuery->fetch())
			{
				switch ($propertyType)
				{
					case Market\Export\Entity\Data::TYPE_ENUM:
						$item = [
							'ID' => $row['ID'],
							'VALUE' => $row['VALUE'],
						];
					break;

					case 'directory':
						$item = [
							'ID' => $row['UF_XML_ID'],
							'VALUE' => $row['UF_NAME'],
						];
					break;

					case 'ElementXmlID':
						$item = [
							'ID' => $row['XML_ID'],
							'VALUE' => $row['NAME'],
						];
					break;

					default:
						$item = [
							'ID' => $row['ID'],
							'VALUE' => '[' . $row['ID'] . '] ' . $row['NAME'],
						];
					break;
				}

				if ((string)$item['ID'] !== '')
				{
					$result[] = $item;
				}
			}
		}

		return $result;
	}

	protected function getSupportAutocompleteTypes()
	{
		return [
			Market\Export\Entity\Data::TYPE_IBLOCK_ELEMENT => true,
			'SKU' => true,
			'ElementXmlID' => true,
			Market\Export\Entity\Data::TYPE_IBLOCK_SECTION => true,
			Market\Export\Entity\Data::TYPE_ENUM => true,
			'directory' => true,
		];
	}

	protected function getDisplayValueFilter($field, $values)
	{
		$result = null;
		$propertyType = $this->getPropertyType($field);

		switch ($propertyType)
		{
			case 'directory':
				$result = [ '=UF_XML_ID' => $values ];
			break;

			case 'ElementXmlID':
				$result = [ '=XML_ID' => $values ];
			break;

			default:
				$result = [ '=ID' => $values ];
			break;
		}

		return $result;
	}

	protected function getAutocompleteFilter($field, $query)
	{
		$result = null;
		$propertyType = $this->getPropertyType($field);

		switch ($propertyType)
		{
			case 'SKU':
			case Market\Export\Entity\Data::TYPE_IBLOCK_ELEMENT:
				$result = [
					[
						'LOGIC' => 'OR',
						[ '%ID' => $query ],
						[ '%NAME' => $query ],
					]
				];
			break;

			case 'ElementXmlID':
				$result = [
					[
						'LOGIC' => 'OR',
						[ '%XML_ID' => $query ],
						[ '%NAME' => $query ],
					]
				];
			break;

			case Market\Export\Entity\Data::TYPE_ENUM:
				$result = [ '%VALUE' => $query ];
			break;

			case 'directory':
				$result = [ '%UF_NAME' => $query ];
			break;

			default:
				$result = [ '%NAME' => $query ];
			break;
		}

		return $result;
	}

	protected function getAutocompleteQuery($field, $filter, $limit = 20)
	{
		$result = null;
		$propertyType = $this->getPropertyType($field);

		switch ($propertyType)
		{
			case 'SKU':
			case Market\Export\Entity\Data::TYPE_IBLOCK_ELEMENT:
				$iblockId = (int)$field['LINK_IBLOCK_ID'];

				if ($iblockId > 0)
				{
					$queryFilter = array_merge([
						'IBLOCK_ID' => $iblockId,
						'ACTIVE' => 'Y',
						'CHECK_PERMISSIONS' => 'Y',
					], $filter);

					$result = \CIBlockElement::GetList(
						[],
						$queryFilter,
						false,
						[ 'nTopCount' => $limit ],
						[ 'ID', 'NAME' ]
					);
				}
			break;

			case 'ElementXmlID':
				$queryFilter = array_merge([
					'ACTIVE' => 'Y',
					'CHECK_PERMISSIONS' => 'Y',
				], $filter);

				$result = \CIBlockElement::GetList(
					[],
					$queryFilter,
					false,
					[ 'nTopCount' => $limit ],
					[ 'XML_ID', 'NAME' ]
				);
			break;

			case Market\Export\Entity\Data::TYPE_IBLOCK_SECTION:
				$iblockId = (int)$field['LINK_IBLOCK_ID'];

				if ($iblockId > 0)
				{
					$queryFilter = array_merge([
						'IBLOCK_ID' => $iblockId,
						'ACTIVE' => 'Y',
						'CHECK_PERMISSIONS' => 'Y',
					], $filter);

					$result = \CIBlockSection::GetList(
						[],
						$queryFilter,
						false,
						[ 'ID', 'NAME' ],
						[ 'nTopCount' => $limit ]
					);
				}
			break;

			case Market\Export\Entity\Data::TYPE_ENUM:
				$queryFilter = array_merge([ 'PROPERTY_ID' => $field['ID'], ], $filter);

				$result = Iblock\PropertyEnumerationTable::getList([
					'filter' => $queryFilter,
					'select' => [ 'ID', 'VALUE' ],
					'limit' => $limit,
				]);
			break;

			case 'directory':
				try
				{
					$highloadEntity = $this->getHighloadEntity($field);

					if (
						$highloadEntity
						&& $highloadEntity->hasField('UF_XML_ID')
						&& $highloadEntity->hasField('UF_NAME')
					)
					{
						$dataClass = $highloadEntity->getDataClass();

						$result = $dataClass::getList([
							'filter' => $filter,
							'select' => [ 'UF_XML_ID', 'UF_NAME' ],
							'limit' => $limit,
						]);
					}
				}
				catch (Main\DB\SqlException $exception)
				{
					 // nothing
				}
			break;
		}

		return $result;
	}

	protected function isAutocompleteQueryMatchType($dataType, $query)
	{
		switch ($dataType)
		{
			case Market\Export\Entity\Data::TYPE_NUMBER:
				$result = is_numeric($query);
			break;

			case Market\Export\Entity\Data::TYPE_IBLOCK_ELEMENT:
			case Market\Export\Entity\Data::TYPE_IBLOCK_SECTION:
				$result = (filter_var($query, FILTER_VALIDATE_INT) !== false);
			break;

			case Market\Export\Entity\Data::TYPE_ENUM:
				$result = false;
			break;

			default:
				$result = true;
			break;
		}

		return $result;
	}

	protected function isAutocompleteResultContainsQuery($options, $query)
	{
		$result = false;

		foreach ($options as $option)
		{
			if ((string)$option['VALUE'] === (string)$query)
			{
				$result = true;
				break;
			}
		}

		return $result;
	}

	protected function getLangPrefix()
	{
		return 'IBLOCK_ELEMENT_PROPERTY_';
	}

	protected function buildQueryFilter($filter)
	{
		$result = [];
		$propertyIds = array_column($filter, 'FIELD');
		$propertyDataList = $this->loadPropertyData($propertyIds, [
			'ID',
			'TYPE',
			'PROPERTY_TYPE',
			'USER_TYPE',
			'USER_TYPE_SETTINGS',
			'MULTIPLE',
			'IBLOCK_ID',
			'LINK_IBLOCK_ID',
		]);
		$subQueryIblockId = null;
		$subQueryFilters = [];

		foreach ($filter as $filterItem)
		{
			$propertyData = null;
			$dataType = null;

			if (isset($propertyDataList[$filterItem['FIELD']]))
			{
				$propertyData = $propertyDataList[$filterItem['FIELD']];
				$dataType = $propertyData['TYPE'];
			}

			$filterValue = $this->convertQueryFilterValue($filterItem['VALUE'], $dataType);

			if (
				!empty($filterItem['STRICT'])
				&& $propertyData !== null
				&& $propertyData['MULTIPLE']
				&& Market\Data\TextString::getPosition($filterItem['COMPARE'], '!') === 0
				&& !Market\Utils\Value::isEmpty($filterValue)
				&& $this->hasFewPropertyVariants($propertyData, $filterValue)
			)
			{
				$invertedCompare = Market\Data\TextString::getSubstring($filterItem['COMPARE'], 1);

				$subQueryIblockId = $propertyData['IBLOCK_ID'];
				$subQueryFilters[] = [
					$invertedCompare . 'PROPERTY_' . $filterItem['FIELD'] => $filterValue,
				];
			}
			else
			{
                $this->pushQueryFilter($result, $filterItem['COMPARE'], 'PROPERTY_' . $filterItem['FIELD'], $filterValue);
            }
        }

		if ($subQueryIblockId !== null && !empty($subQueryFilters))
		{
			if (count($subQueryFilters) > 1)
			{
				$subQueryFilters['LOGIC'] = 'OR';
			}

			$subQuery = $this->makeSubQueryFilter($subQueryIblockId, $subQueryFilters);

			$this->pushQueryFilter($result, '!=', 'ID', $subQuery);
		}

        return $result;
	}

	protected function convertQueryFilterValue($value, $dataType)
	{
		switch ($dataType)
		{
			case Market\Export\Entity\Data::TYPE_DATETIME:
				$result = $this->convertQueryFilterDate($value, 'Y-m-d H:i:s');
			break;

			case Market\Export\Entity\Data::TYPE_DATE:
				$result = $this->convertQueryFilterDate($value, 'Y-m-d');
			break;

			default:
				$result = $value;
			break;
		}

		return $result;
	}

	protected function convertQueryFilterDate($value, $format)
	{
		$isMultiple = is_array($value);
		$valueIterable = $isMultiple ? $value : [ $value ];
		$newValue = [];

		foreach ($valueIterable as $valueItem)
		{
			if (Market\Data\DateInterval::isValid($valueItem))
			{
				$valueDate = new Main\Type\DateTime();
				$valueDate->add($valueItem);

				$newValue[] = $valueDate->format($format);
			}
			else
			{
				$valueItemTimestamp = MakeTimeStamp($valueItem);

				if ($valueItemTimestamp !== false)
				{
					$newValue[] = date($format, $valueItemTimestamp);
				}
				else
				{
					$newValue[] = $valueItem;
				}
			}
		}

		return $isMultiple ? $newValue : reset($newValue);
	}

	protected function makeSubQueryFilter($iblockId, $filter)
	{
		return \CIBlockElement::SubQuery('ID', [
			'IBLOCK_ID' => $iblockId,
			'ACTIVE' => 'Y',
			'ACTIVE_DATE' => 'Y',
			$filter,
		]);
	}

	protected function getPropertyValues($iblockId, $elementIds, $select, $queryContext, $originalPropertyIds = null)
	{
		$isNeedDiscountCache = (!empty($queryContext['DISCOUNT_CACHE']) && Main\Loader::includeModule('catalog'));
		$isNeedSelectAll = $isNeedDiscountCache && empty($queryContext['DISCOUNT_PROPERTIES_OPTIMIZATION']);
		$parsedSelect = $this->parseSelect($select);
		$propertyIds = $parsedSelect['PROPERTY_ID'];
		$propertyValuesList = $this->queryProperties($iblockId, $elementIds, $propertyIds, $isNeedSelectAll);
		$result = [];

		if ($isNeedDiscountCache)
		{
			foreach ($propertyValuesList as $elementId => $propertyList)
			{
				if (!empty($queryContext['DISCOUNT_ONLY_SALE']))
				{
					if (\method_exists('\Bitrix\Catalog\Discount\DiscountManager', 'setProductPropertiesCache'))
					{
						Catalog\Discount\DiscountManager::setProductPropertiesCache($elementId, $propertyList);
					}
				}
				else
				{
					\CCatalogDiscount::SetProductPropertiesCache($elementId, $propertyList);
				}
			}
		}

		$this->extendSelectInnerDefaults($parsedSelect, $propertyValuesList);

		$this->extendInnerValue($result, $propertyValuesList, $parsedSelect['INNER_FIELD'], $parsedSelect['NAME_MAP'], $queryContext);
		$this->extendPlainValue($result, $propertyValuesList, $parsedSelect['PLAIN_FIELD'], $parsedSelect['NAME_MAP'], $queryContext);

		return $result;
	}

	protected function parseSelect($select)
	{
		$result = [
			'PROPERTY_ID' => [],
			'PLAIN_FIELD' => [],
			'INNER_FIELD' => [],
			'NAME_MAP' => []
		];
		$plainFields = [
			'VALUE' => true,
			'VALUE_ENUM_ID' => true,
			'VALUE_XML_ID' => true,
			'DISPLAY_VALUE' => true,
			'DESCRIPTION' => true,
		];
		$propertyIds = [];

		foreach ($select as $field)
		{
			$dotPosition = Market\Data\TextString::getPosition($field, '.');
			$propertyId = null;
			$propertyField = null;

			if ($dotPosition !== false)
			{
				$propertyId = (int)Market\Data\TextString::getSubstring($field, 0, $dotPosition);
				$propertyField = Market\Data\TextString::getSubstring($field, $dotPosition + 1);
			}
			else
			{
				$propertyId = (int)$field;
				$propertyField = 'DISPLAY_VALUE';

				$result['NAME_MAP'][$propertyId . '.' . $propertyField] = $propertyId;
			}

			$propertyIds[$propertyId] = true;

			if (isset($plainFields[$propertyField]))
			{
				if (!isset($result['PLAIN_FIELD'][$propertyId]))
				{
					$result['PLAIN_FIELD'][$propertyId] = [ $propertyField ];
				}
				else if (!in_array($propertyField, $result['PLAIN_FIELD'][$propertyId], true))
				{
					$result['PLAIN_FIELD'][$propertyId][] = $propertyField;
				}
			}
			else
			{
				if (!isset($result['INNER_FIELD'][$propertyId]))
				{
					$result['INNER_FIELD'][$propertyId] = [ $propertyField ];
				}
				else if (!in_array($propertyField, $result['INNER_FIELD'][$propertyId], true))
				{
					$result['INNER_FIELD'][$propertyId][] = $propertyField;
				}
			}
		}

		$result['PROPERTY_ID'] = array_keys($propertyIds);

		return $result;
	}

	protected function extendSelectInnerDefaults(&$parsedSelect, $propertyValuesList)
	{
		if (!empty($propertyValuesList) && !empty($parsedSelect['PLAIN_FIELD']))
		{
			$propertyList = $this->getFilledElementPropertyValues($propertyValuesList);
			$optimizedTypes = [
				'E' => 'NAME',
				'F' => 'SRC',
				'directory' => 'UF_NAME',
				'ElementXmlID' => 'NAME',
			];

			foreach ($propertyList as $property)
			{
				if (isset($parsedSelect['PLAIN_FIELD'][$property['ID']]))
				{
					$displayValueIndex = array_search('DISPLAY_VALUE', $parsedSelect['PLAIN_FIELD'][$property['ID']], true);

					if ($displayValueIndex !== false)
					{
						$propertyType = $this->getPropertyType($property);

						if (isset($optimizedTypes[$propertyType]))
						{
							// remove from plain

							array_splice($parsedSelect['PLAIN_FIELD'][$property['ID']], $displayValueIndex, 1);

							if (empty($parsedSelect['PLAIN_FIELD'][$property['ID']]))
							{
								unset($parsedSelect['PLAIN_FIELD'][$property['ID']]);
							}

							// add to inner

							if (!isset($parsedSelect['INNER_FIELD'][$property['ID']]))
							{
								$parsedSelect['INNER_FIELD'][$property['ID']] = [];
							}

							$innerField = $optimizedTypes[$propertyType];

							if (!in_array($innerField, $parsedSelect['INNER_FIELD'][$property['ID']], true))
							{
								$parsedSelect['INNER_FIELD'][$property['ID']][] = $innerField;
							}

							// add to map

							$parsedSelect['NAME_MAP'][$property['ID'] . '.' . $innerField] = $property['ID'];
						}
					}
				}
			}
		}
	}

	protected function queryProperties($iblockId, $elementIds, $propertyIds, $isSelectAll = false)
	{
		$result = [];

		if (
			(!empty($propertyIds) || $isSelectAll)
			&& Main\Loader::includeModule('iblock')
		)
		{
			// build result for iblock method

			foreach ($elementIds as $elementId)
			{
				$result[$elementId] = [];
			}

			// query values

			\CIBlockElement::GetPropertyValuesArray($result, $iblockId, ['ID' => $elementIds], $isSelectAll ? [] : ['ID' => $propertyIds]);
		}

		return $result;
	}

	protected function extendInnerValue(&$result, $propertyValuesList, $selectMap, $nameMap, $context)
	{
		$valuesMap = $this->extractPropertyValuesListField($propertyValuesList, $selectMap);
		$propertyList = $this->getFilledElementPropertyValues($propertyValuesList);
		$propertyListMap = [];

		foreach ($propertyList as $propertyKey => $property)
		{
			$propertyListMap[$property['ID']] = $propertyKey;
		}

		foreach ($valuesMap as $propertyId => $propertyValuesToElementMap)
		{
			if (isset($propertyListMap[$propertyId]))
			{
				$propertySelect = $selectMap[$propertyId];
				$propertyKey = $propertyListMap[$propertyId];
				$property = $propertyList[$propertyKey];
				$propertyType = $this->getPropertyType($property);
				$propertyValues = array_keys($propertyValuesToElementMap);
				$innerResult = [];

				switch ($propertyType)
				{
					case 'E':
					case 'SKU':
					case 'ElementXmlID':
						$propertyFieldMarker = 'PROPERTY_';
						$innerFieldSelect = [];
						$innerPropertySelect = [];
						$elementListByIblock = [];

						if ($propertyType === 'ElementXmlID')
						{
							$innerLinkField = 'XML_ID';
							$elementMap = [];
						}
						else
						{
							$innerLinkField = 'ID';
							$elementMap = null;
						}

						foreach ($propertySelect as $field)
						{
							if (Market\Data\TextString::getPosition($field, $propertyFieldMarker) === 0)
							{
								$innerPropertySelect[] = str_replace($propertyFieldMarker, '', $field);
							}
							else
							{
								$innerFieldSelect[] = $field;
							}
						}

						$queryElementList = \CIBlockElement::GetList(
							[],
							[ '=' . $innerLinkField => $propertyValues ],
							false,
							false,
							array_merge(
								[ 'IBLOCK_ID', 'ID', $innerLinkField ],
								$innerFieldSelect
							)
						);

						while ($element = $queryElementList->Fetch())
						{
							$elementIblockId = (int)$element['IBLOCK_ID'];

							if (!isset($elementListByIblock[$elementIblockId]))
							{
								$elementListByIblock[$elementIblockId] = [];
							}

							$elementListByIblock[$elementIblockId][$element['ID']] = $element;

							if ($elementMap !== null && !in_array($element[$innerLinkField], $elementMap, true))
							{
								$elementMap[$element['ID']] = $element[$innerLinkField];
							}
						}

						foreach ($elementListByIblock as $innerIblockId => $elementList)
						{
							$innerContext = Market\Export\Entity\Iblock\Provider::getContext($innerIblockId);
							$elementResult = [];

							if (!empty($innerFieldSelect))
							{
								$fieldSource = Market\Export\Entity\Manager::getSource(
									Market\Export\Entity\Manager::TYPE_IBLOCK_ELEMENT_FIELD
								);

								$elementResult = $fieldSource->getElementListValues($elementList, [], $innerFieldSelect, $innerContext, []);
							}

							if (!empty($innerPropertySelect))
							{
								$propertySource = Market\Export\Entity\Manager::getSource(
									Market\Export\Entity\Manager::TYPE_IBLOCK_ELEMENT_PROPERTY
								);

								$innerValues = $propertySource->getElementListValues($elementList, [], $innerPropertySelect, $innerContext, []);

								foreach ($innerValues as $elementId => $elementInnerValues)
								{
									if (!isset($elementResult[$elementId]))
									{
										$elementResult[$elementId] = [];
									}

									foreach ($elementInnerValues as $fieldName => $fieldValue)
									{
										$elementResult[$elementId]['PROPERTY_' . $fieldName] = $fieldValue;
									}
								}
							}

							if ($elementMap === null)
							{
								$innerResult += $elementResult;
							}
							else
							{
								foreach ($elementResult as $elementId => $elementValues)
								{
									if (!isset($elementMap[$elementId])) { continue; }

									$elementKey = $elementMap[$elementId];
									$innerResult[$elementKey] = $elementValues;
								}
							}
						}
					break;

					case 'G':
						foreach ($this->splitIblockSectionsByIblockId($property, $propertyValues) as $iblockId => $sectionIds)
						{
							$sectionSource = Market\Export\Entity\Manager::getSource(Market\Export\Entity\Manager::TYPE_IBLOCK_SECTION);
							$sectionContext = Market\Export\Entity\Iblock\Provider::getContext($iblockId) + $context;
							$sectionEmulatedRows = [];

							foreach ($sectionIds as $sectionId)
							{
								$sectionEmulatedRows[$sectionId] = [
									'IBLOCK_ID' => $iblockId,
									'ID' => $sectionId,
								];
							}

							if (!($sectionSource instanceof Market\Export\Entity\Reference\HasSectionValues))
							{
								throw new Main\NotImplementedException(sprintf(
									'Source %s must implements %s',
									Market\Export\Entity\Manager::TYPE_IBLOCK_SECTION,
									Market\Export\Entity\Reference\HasSectionValues::class
								));
							}

							$innerResult += $sectionSource->getSectionListValues($sectionEmulatedRows, $propertySelect, $sectionContext);
						}
					break;

					case 'F':
						$query = \CFile::GetList([], ['@ID' => $propertyValues]);

						while ($row = $query->Fetch())
						{
							$row['SRC'] = \CFile::GetFileSRC($row);

							$innerResult[$row['ID']] = $row;
						}
					break;

					case 'directory':
						try
						{
							$highloadEntity = $this->getHighloadEntity($property);

							if ($highloadEntity && $highloadEntity->hasField('UF_XML_ID'))
							{
								$highloadDataClass = $highloadEntity->getDataClass();

								$queryEnum = $highloadDataClass::getList([
									'filter' => [
										'=UF_XML_ID' => $propertyValues,
									]
								]);

								while ($enum = $queryEnum->fetch())
								{
									$innerResult[$enum['UF_XML_ID']] = $enum;
								}
							}
						}
						catch (Main\DB\SqlException $exception)
						{
							// nothing
						}
					break;
				}

				// fill display value

				foreach ($propertyValuesToElementMap as $innerId => $elementIds)
				{
					if (!isset($innerResult[$innerId])) { continue; }

					$innerFields = $innerResult[$innerId];

					foreach ($elementIds as $elementId)
					{
						if (!isset($result[$elementId]))
						{
							$result[$elementId] = [];
						}

						foreach ($propertySelect as $fieldName)
						{
							$resultKey = $property['ID'] . '.' . $fieldName;

							if (isset($innerFields[$fieldName]))
							{
								$innerValue = $innerFields[$fieldName];

								if (isset($result[$elementId][$resultKey]))
								{
									if (!is_array($result[$elementId][$resultKey]))
									{
										$result[$elementId][$resultKey] = (array)$result[$elementId][$resultKey];
									}

									$result[$elementId][$resultKey][] = $innerValue;
								}
								else
								{
									$result[$elementId][$resultKey] = $innerValue;
								}

								if (isset($nameMap[$resultKey]))
								{
									$result[$elementId][$nameMap[$resultKey]] = $result[$elementId][$resultKey];
								}
							}
						}
					}
				}
			}
		}
	}

	protected function getFilledElementPropertyValues($propertyValuesList)
	{
		$result = [];

		foreach ($propertyValuesList as $propertyList)
		{
			if (empty($propertyList)) { continue; }

			$result = $propertyList;
			break;
		}

		return $result;
	}

	protected function splitIblockSectionsByIblockId($property, $sectionIds)
	{
		if (!empty($property['LINK_IBLOCK_ID']))
		{
			$iblockId = (int)$property['LINK_IBLOCK_ID'];

			return [
				$iblockId => $sectionIds,
			];
		}

		$result = [];

		$query = Iblock\SectionTable::getList([
			'filter' => [ '=ID' => $sectionIds ],
			'select' => [ 'IBLOCK_ID', 'ID' ]
		]);

		while ($row = $query->fetch())
		{
			$iblockId = (int)$row['IBLOCK_ID'];

			if (!isset($result[$iblockId]))
			{
				$result[$iblockId] = [];
			}

			$result[$iblockId][] = (int)$row['ID'];
		}

		return $result;
	}

	protected function extendPlainValue(&$result, $propertyValuesList, $selectMap, $nameMap, $context)
	{
		if (!empty($selectMap) && !empty($propertyValuesList))
		{
			foreach ($propertyValuesList as $elementId => $propertyList)
			{
				foreach ($propertyList as $property)
				{
					if (isset($selectMap[$property['ID']]))
					{
						$propertySelect = $selectMap[$property['ID']];

						foreach ($propertySelect as $fieldName)
						{
							$fieldValue = null;

							if ($fieldName === 'DISPLAY_VALUE')
							{
								$fieldValue = $this->getDisplayValue($property);
							}
							else if (isset($property[$fieldName]))
							{
								$fieldValue = $property[$fieldName];
							}

							if ($fieldValue !== null)
							{
								$resultKey = $property['ID'] . '.' . $fieldName;

								if (!isset($result[$elementId]))
								{
									$result[$elementId] = [];
								}

								$result[$elementId][$resultKey] = $fieldValue;

								if (isset($nameMap[$resultKey]))
								{
									$result[$elementId][$nameMap[$resultKey]] = $fieldValue;
								}
							}
						}
					}
				}
			}
		}
	}

	protected function extractPropertyValuesListField($propertyValuesList, $usedMap, $field = 'VALUE')
	{
		$result = [];

		foreach ($propertyValuesList as $elementId => $propertyList)
		{
			foreach ($propertyList as $property)
			{
				if (!empty($property[$field]) && isset($usedMap[$property['ID']]))
				{
					$propertyId = (int)$property['ID'];

					if (!isset($result[$propertyId]))
					{
						$result[$propertyId] = [];
					}

					if (is_array($property[$field]))
					{
						foreach ($property[$field] as $value)
						{
							$value = trim($value);

							if ($value !== '')
							{
								if (!isset($result[$propertyId][$value]))
								{
									$result[$propertyId][$value] = [];
								}

								$result[$propertyId][$value][] = $elementId;
							}
						}
					}
					else
					{
						$value = trim($property[$field]);

						if ($value !== '')
						{
							if (!isset($result[$propertyId][$value]))
							{
								$result[$propertyId][$value] = [];
							}

							$result[$propertyId][$value][] = $elementId;
						}
					}
				}
			}
		}

		return $result;
	}

	protected function getDisplayValue($property)
	{
		$result = null;

		if (isset($property['VALUE']) && !$this->isEmptyValue($property['VALUE']))
		{
			switch ($this->getPropertyType($property))
			{
				case 'F':
					$fileIds = (array)$property['VALUE'];
					$result = [];

					foreach ($fileIds as $fileId)
					{
						$result[] = \CFile::GetPath($fileId);
					}
				break;

				case 'HTML':
					$propertyValue = isset($property['~VALUE']) ? $property['~VALUE'] : $property['VALUE'];
					$valueList = $propertyValue;

					if (isset($propertyValue['TEXT'], $propertyValue['TYPE']))
					{
						$valueList = [ $propertyValue ];
					}

					if (is_array($valueList))
					{
						$isMultipleProperty = ($property['MULTIPLE'] === 'Y');

						foreach ($valueList as $value)
						{
							if (!isset($value['TEXT'], $value['TYPE'])) { continue; }

							$displayValue = $this->makeHtmlDisplayValue($value['TEXT'], $value['TYPE']);

							if ($isMultipleProperty)
							{
								if ($result === null) { $result = []; }

								$result[] = $displayValue;
							}
							else
							{
								$result = $displayValue;
							}
						}
					}
				break;

				default:
					$result = (isset($property['~VALUE']) ? $property['~VALUE'] : htmlspecialcharsback($property['VALUE']));
				break;
			}
		}

		return $result;
	}

	protected function getPropertyType($property)
	{
		$result = $property['PROPERTY_TYPE'];

		if (isset($this->specialTypes[$property['USER_TYPE']]))
		{
			$result = $property['USER_TYPE'];
		}

		return $result;
	}

	protected function makeHtmlDisplayValue($text, $type)
	{
		if (Market\Data\TextString::toLower($type) !== 'html')
		{
			$text = $this->textToHtml($text);
		}

		return $text;
	}

	protected function textToHtml($str)
	{
		$str = trim($str);
		$str = str_replace(
			['<', '>', "\r\n", "\n"],
			['&lt;', '&gt;', "\n", "<br />\n"],
			$str
		);

		return $str;
	}

	/**
	 * @param $property
	 *
	 * @return \Bitrix\Main\Entity\Base|false
	 * @throws \Bitrix\Main\LoaderException
	 */
	protected function getHighloadEntity($property)
	{
		$result = false;
		$tableName = !empty($property['USER_TYPE_SETTINGS']['TABLE_NAME'])
			? $property['USER_TYPE_SETTINGS']['TABLE_NAME']
			: null;

		if ($tableName === null)
		{
			// nothing
		}
		else if (isset($this->highloadDataClassCache[$tableName]))
		{
			$result = $this->highloadDataClassCache[$tableName];
		}
		else if (Main\Loader::includeModule('highloadblock'))
		{
			$queryHighload = Highloadblock\HighloadBlockTable::getList([
				'filter' => ['=TABLE_NAME' => $tableName],
			]);

			if ($highload = $queryHighload->fetch())
			{
				$result = Highloadblock\HighloadBlockTable::compileEntity($highload);
			}

			$this->highloadDataClassCache[$tableName] = $result;
		}

		return $result;
	}

	protected function isEmptyValue($value)
	{
		if (is_scalar($value))
		{
			$result = ((string)$value === '');
		}
		else
		{
			$result = empty($value);
		}

		return $result;
	}
}
