<?php

namespace Yandex\Market\Export\Entity\Iblock\Element\Field;

use Yandex\Market;
use Bitrix\Main;
use Bitrix\Iblock;

Main\Localization\Loc::loadMessages(__FILE__);

class Source extends Market\Export\Entity\Reference\Source
{
	protected $sectionFilter = [];
	protected $sectionRule;
	protected $defaultSectionRule;
	protected $previousSectionFilter = [];
	protected $previousSectionRule;
	protected $invalidValuesCache = [];

	public function getQuerySelect($select)
	{
		$entityType = $this->getQueryEntityType();

		return [
			$entityType => $select
		];
	}

	public function isFilterable()
	{
		return true;
	}

	public function initializeFilterContext($filter, &$queryContext, &$sourceFilter)
	{
		$this->initializeActiveFilter($filter, $queryContext);
		$this->initializeSectionActiveFilter($filter, $queryContext);
		$this->initializeSectionRule($filter);
		$this->initializeSectionFilter($filter);

		if ($this->isChangedSectionFilter())
		{
			$this->flushSectionsInvalidCache();
		}
	}

	protected function initializeActiveFilter($filter, &$queryContext)
	{
		$activeFilter = $this->extractFilterItems($filter, [ 'ACTIVE' => true ]);

		if (empty($activeFilter)) { return; }

		$this->registerContextManualFilter($queryContext, 'ACTIVE');
	}

	protected function initializeSectionActiveFilter($filter, &$queryContext)
	{
		$sectionActiveFilter = $this->extractFilterItems($filter, [ 'SECTION_ACTIVE' => true ]);

		if (empty($sectionActiveFilter)) { return; }

		$this->registerContextManualFilter($queryContext, 'SECTION_ACTIVE');
	}

	protected function registerContextManualFilter(&$queryContext, $field)
	{
		$entityType = $this->getQueryEntityType();

		if (!isset($queryContext['FILTER_MANUAL']))
		{
			$queryContext['FILTER_MANUAL'] = [];
		}

		if (!isset($queryContext['FILTER_MANUAL'][$entityType]))
		{
			$queryContext['FILTER_MANUAL'][$entityType] = [];
		}

		$queryContext['FILTER_MANUAL'][$entityType][$field] = true;
	}

	protected function initializeSectionRule($filter)
	{
		$rulesFilter = $this->extractFilterItems($filter, [ 'SECTION_ACTIVE' => true ]);
		$rules = $this->makeFilterRules($rulesFilter);

		if (isset($rules['SECTION_ACTIVE']))
		{
			$this->sectionRule = $this->sanitizeSectionRule($rules['SECTION_ACTIVE']);
		}
		else
		{
			$this->sectionRule = null;
		}
	}

	protected function getSectionRule()
	{
		return $this->sectionRule !== null ? $this->sectionRule : $this->getDefaultSectionRule();
	}

	protected function getDefaultSectionRule()
	{
		if ($this->defaultSectionRule === null)
		{
			$this->defaultSectionRule = $this->buildDefaultSectionRule();
		}

		return $this->defaultSectionRule;
	}

	protected function buildDefaultSectionRule()
	{
		$defaultRules = $this->getFilterDefaultRules();
		$sectionRule = isset($defaultRules['SECTION_ACTIVE']) ? $defaultRules['SECTION_ACTIVE'] : [];

		return $this->sanitizeSectionRule($sectionRule);
	}

	protected function sanitizeSectionRule($filter)
	{
		if (!is_array($filter)) { return null; }

		$result = [];
		$tableFields = Iblock\SectionTable::getEntity()->getScalarFields();

		foreach ($filter as $name => $value)
		{
			if (!preg_match('/^(\W*)SECTION_(.+)$/', $name, $matches)) { continue; }

			list(, $compare, $field) = $matches;

			if (isset($tableFields[$field]))
			{
				if ($compare === '')
				{
					$compare = '=';
				}

				$result[$compare . $field] = $value;
			}
		}

		return $result;
	}

	protected function initializeSectionFilter($filter)
	{
		$filter = $this->sliceQueryFilter($filter, [
			'IBLOCK_SECTION_ID',
			'SECTION_ID',
			'STRICT_SECTION_ID',
		]);
		$filter = $this->groupQueryFilter($filter);

		foreach ($filter as $filterItem)
		{
			if (empty($filterItem['VALUE'])) { continue; }

			if ($filterItem['FIELD'] === 'IBLOCK_SECTION_ID' || $filterItem['FIELD'] === 'SECTION_ID')
			{
				$isCompareEqual = (Market\Data\TextString::getPosition($filterItem['COMPARE'], '!') !== 0);
				$sectionMargins = $this->loadSectionMargin($filterItem['VALUE']);

				if ($this->hasSectionChild($sectionMargins))
				{
					$filterByMargins = [];

					if ($isCompareEqual && count($sectionMargins) > 1)
					{
						$filterByMargins['LOGIC'] = 'OR';
					}

					foreach ($sectionMargins as $sectionMargin)
					{
						if ($isCompareEqual)
						{
							$filterByMargins[] = [
								'>=LEFT_MARGIN' => $sectionMargin[0],
								'<=RIGHT_MARGIN' => $sectionMargin[1],
							];
						}
						else
						{
							$filterByMargins[] = [
								'LOGIC' => 'OR',
								'<RIGHT_MARGIN' => $sectionMargin[0],
								'>LEFT_MARGIN' => $sectionMargin[1],
							];
						}
					}

					$this->sectionFilter[] = $filterByMargins;
				}
				else
				{
					$this->pushQueryFilter($this->sectionFilter, $filterItem['COMPARE'], 'ID', $filterItem['VALUE']);
				}
			}
			else if ($filterItem['FIELD'] === 'STRICT_SECTION_ID')
			{
				$this->pushQueryFilter($this->sectionFilter, $filterItem['COMPARE'], 'ID', $filterItem['VALUE']);
			}
		}
	}

	protected function sliceQueryFilter($filter, $include)
	{
		$includeMap = array_flip($include);
		$result = [];

		foreach ($filter as $item)
		{
			if (!isset($includeMap[$item['FIELD']])) { continue; }

			$result[] = $item;
		}

		return $result;
	}

	protected function groupQueryFilter($filter)
	{
		$fieldMap = [];
		$index = 0;
		$result = [];

		foreach ($filter as $item)
		{
			$sign = $item['COMPARE'] . $item['FIELD'];

			if (!isset($fieldMap[$sign]))
			{
				$result[$index] = $item;
				$fieldMap[$sign] = $index;

				++$index;
			}
			else
			{
				$previousIndex = $fieldMap[$sign];
				$previous = &$result[$previousIndex];
				$newValue = is_array($previous['VALUE']) ? $previous['VALUE'] : [ $previous['VALUE'] ];

				if (is_array($item['VALUE']))
				{
					/** @noinspection SlowArrayOperationsInLoopInspection */
					$newValue = array_merge($newValue, $item['VALUE']);
				}
				else
				{
					$newValue[] = $item['VALUE'];
				}

				$previous['VALUE'] = $newValue;
				unset($previous);
			}
		}

		return $result;
	}

	protected function isChangedSectionFilter()
	{
		return (
			$this->previousSectionFilter !== $this->sectionFilter
			|| $this->previousSectionRule !== $this->sectionRule
		);
	}

	protected function flushSectionsInvalidCache()
	{
		$type = Market\Export\Entity\Data::TYPE_IBLOCK_SECTION;

		if (isset($this->invalidValuesCache[$type]))
		{
			$this->invalidValuesCache[$type] = [];
		}
	}

	public function releaseFilterContext($filter, $queryContext, $sourceFilter)
	{
		$this->previousSectionFilter = $this->sectionFilter;
		$this->previousSectionRule = $this->sectionRule;
		$this->sectionFilter = [];
		$this->sectionRule = null;
	}

	public function getQueryFilter($filter, $select)
	{
		$entityType = $this->getQueryEntityType();

		return [
			$entityType => $this->buildQueryFilter($filter)
		];
	}

	protected function getQueryEntityType()
	{
		return 'ELEMENT';
	}

	public function getOrder()
	{
		return 100;
	}

	public function getElementListValues($elementList, $parentList, $select, $queryContext, $sourceValues)
	{
		$result = [];
		$searchElements = [];

		foreach ($elementList as $elementId => $element)
		{
			if (!isset($element['PARENT_ID'])) // is not offer
			{
				$searchElements[$elementId] = $element;
			}
			else if (isset($parentList[$element['PARENT_ID']])) // has parent element
			{
				$searchElements[$elementId] = $parentList[$element['PARENT_ID']];
			}
		}

		$this->preloadFieldValues($searchElements, $select, $queryContext);
		$this->resolveInvalidValues($searchElements, $select, $queryContext);

		foreach ($searchElements as $elementId => $parent)
		{
			$result[$elementId] = $this->getFieldValues($parent, $select, null, $queryContext);
		}

		return $result;
	}

	public function getFields(array $context = [])
	{
		return $this->buildFieldsDescription([
			'ID' => [
				'TYPE' => Market\Export\Entity\Data::TYPE_NUMBER,
                'AUTOCOMPLETE' => true,
                'AUTOCOMPLETE_FIELD' => 'NAME'
			],
			'NAME' => [
				'TYPE' => Market\Export\Entity\Data::TYPE_STRING,
                'AUTOCOMPLETE' => true,
			],
			'ACTIVE' => [
				'TYPE' => Market\Export\Entity\Data::TYPE_BOOLEAN,
				'SELECTABLE' => false,
			],
			'IBLOCK_SECTION_ID' => [
				'TYPE' => Market\Export\Entity\Data::TYPE_IBLOCK_SECTION,
				'AUTOCOMPLETE' => true,
				'AUTOCOMPLETE_PRIMARY' => 'ID',
				'AUTOCOMPLETE_FIELD' => 'NAME',
			],
			'STRICT_SECTION_ID' => [
				'TYPE' => Market\Export\Entity\Data::TYPE_IBLOCK_SECTION,
				'SELECTABLE' => false,
				'AUTOCOMPLETE' => true,
				'AUTOCOMPLETE_PRIMARY' => 'ID',
				'AUTOCOMPLETE_FIELD' => 'NAME',
			],
			'SECTION_ACTIVE' => [
				'TYPE' => Market\Export\Entity\Data::TYPE_BOOLEAN,
				'SELECTABLE' => false,
			],
			'CODE'=> [
				'TYPE' => Market\Export\Entity\Data::TYPE_STRING,
                'AUTOCOMPLETE' => true,
                'AUTOCOMPLETE_FIELD' => 'NAME'
			],
			'SORT' => [
				'TYPE' => Market\Export\Entity\Data::TYPE_NUMBER,
			],
			'PREVIEW_PICTURE' => [
				'TYPE' => Market\Export\Entity\Data::TYPE_FILE
			],
			'PREVIEW_TEXT' => [
				'TYPE' => Market\Export\Entity\Data::TYPE_STRING
			],
			'DETAIL_PICTURE' => [
				'TYPE' => Market\Export\Entity\Data::TYPE_FILE
			],
			'DETAIL_TEXT' => [
				'TYPE' => Market\Export\Entity\Data::TYPE_STRING
			],
			'DETAIL_PAGE_URL' => [
				'TYPE' => Market\Export\Entity\Data::TYPE_URL,
				'FILTERABLE' => false,
			],
			'CANONICAL_PAGE_URL' => [
				'TYPE' => Market\Export\Entity\Data::TYPE_URL,
				'FILTERABLE' => false,
			],
			'DATE_CREATE' => [
				'TYPE' => Market\Export\Entity\Data::TYPE_DATETIME
			],
			'TIMESTAMP_X' => [
				'TYPE' => Market\Export\Entity\Data::TYPE_DATETIME
			],
			'DATE_ACTIVE_FROM' => [
				'TYPE' => Market\Export\Entity\Data::TYPE_DATETIME
			],
			'DATE_ACTIVE_TO' => [
				'TYPE' => Market\Export\Entity\Data::TYPE_DATETIME
			],
			'XML_ID' => [
				'TYPE' => Market\Export\Entity\Data::TYPE_STRING
			],
			'CHECK_PERMISSIONS' => [
				'TYPE' => Market\Export\Entity\Data::TYPE_BOOLEAN,
				'SELECTABLE' => false,
			],
			'PERMISSIONS_BY' => [
				'TYPE' => Market\Export\Entity\Data::TYPE_NUMBER,
				'SELECTABLE' => false,
			],
			'TAGS' => [
				'TYPE' => Market\Export\Entity\Data::TYPE_STRING,
			],
		]);
	}

	public function getFieldEnum($field, array $context = [])
	{
		$result = null;
		$limit = 50;

		if ($field['TYPE'] === Market\Export\Entity\Data::TYPE_IBLOCK_SECTION)
		{
			$dbQuery = $this->getAutocompleteQuery($field, [], $limit + 1, $context);

			$result = $this->getAutocompleteResultItems($field, $dbQuery);
		}
		else if ($field['ID'] === 'ACTIVE')
		{
			$result = $this->getFieldActiveEnum();
		}
		else if ($field['ID'] === 'SECTION_ACTIVE')
		{
			$result = $this->getFieldSectionActiveEnum();
		}

		if ($result === null)
		{
			$result = parent::getFieldEnum($field, $context);
		}
		else if (!empty($field['AUTOCOMPLETE']) && count($result) > $limit)
		{
			$result = null;
		}

		return $result;
	}

	protected function getFieldActiveEnum()
	{
		return $this->buildFieldEnum('ACTIVE', [
			'Y',
			'N',
			'DATE',
			'ANY',
		]);
	}

	protected function getFieldSectionActiveEnum()
	{
		return $this->buildFieldEnum('SECTION_ACTIVE', [
			'Y',
			'GLOBAL',
			'ANY',
		]);
	}

	protected function buildFieldEnum($field, $variants)
	{
		$result = [];
		$langPrefix = $this->getLangPrefix();

		foreach ($variants as $variant)
		{
			$langKey = sprintf('%sFIELD_%s_ENUM_%s', $langPrefix, $field, $variant);

			$result[] = [
				'ID' => $variant,
				'VALUE' => Market\Config::getLang($langKey, null, $variant),
			];
		}

		return $result;
	}

	public function getFieldAutocomplete($field, $query, array $context = [])
    {
    	$limit = 20;
	    $filter = $this->getAutocompleteFilter($field, $query);
	    $dbQuery = $this->getAutocompleteQuery($field, $filter, $limit, $context);
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
        $result = null;
	    $limit = count($valueList);
	    $filter = $this->getDisplayValueFilter($field, $valueList);
	    $dbQuery = null;

	    if ($filter !== null)
	    {
		    $dbQuery = $this->getAutocompleteQuery($field, $filter, $limit, $context);
	    }

	    return $this->getAutocompleteResultItems($field, $dbQuery);
    }

    protected function getAutocompleteFilter($field, $query)
    {
	    $primaryField = isset($field['AUTOCOMPLETE_PRIMARY']) ? $field['AUTOCOMPLETE_PRIMARY'] : $field['ID'];
	    $searchField = isset($field['AUTOCOMPLETE_FIELD']) ? $field['AUTOCOMPLETE_FIELD'] : $primaryField;

	    if ($field['TYPE'] === Market\Export\Entity\Data::TYPE_IBLOCK_SECTION)
	    {
		    $result = [
			    '%' . $searchField => $query,
		    ];
	    }
	    else if ($primaryField !== $searchField)
	    {
		    $result = [
			    'LOGIC' => 'OR',
			    [ '%' . $primaryField => $query ],
			    [ '%' . $searchField => $query ],
		    ];
	    }
	    else
	    {
		    $result = [
		    	'%' . $primaryField => $query,
		    ];
	    }

	    return $result;
    }

	protected function getDisplayValueFilter($field, $values)
	{
		$result = null;

		if (isset($field['AUTOCOMPLETE_FIELD']))
		{
			$primaryField = isset($field['AUTOCOMPLETE_PRIMARY']) ? $field['AUTOCOMPLETE_PRIMARY'] : $field['ID'];

			$result = [
				'=' . $primaryField => $values,
			];
		}

		return $result;
	}

    protected function getAutocompleteQuery($field, $filter, $limit, $context)
    {
    	$iblockId = (int)$this->getContextIblockId($context);
	    $result = null;

    	if ($iblockId <= 0) { return null; }

	    $primaryField = isset($field['AUTOCOMPLETE_PRIMARY']) ? $field['AUTOCOMPLETE_PRIMARY'] : $field['ID'];
	    $searchField = isset($field['AUTOCOMPLETE_FIELD']) ? $field['AUTOCOMPLETE_FIELD'] : $primaryField;

        if ($field['TYPE'] === Market\Export\Entity\Data::TYPE_IBLOCK_SECTION)
	    {
	        $queryFilter = [
			    'IBLOCK_ID' => $context['IBLOCK_ID'],
			    'ACTIVE' => 'Y',
			    'CHECK_PERMISSIONS' => 'N',
		    ];
	        $queryFilter = array_merge($queryFilter, $filter);

		    $querySelect = [
			    $primaryField,
			    $searchField,
			    'DEPTH_LEVEL',
		    ];

		    $result = \CIBlockSection::getList(
			    [ 'LEFT_MARGIN' => 'ASC' ],
			    $queryFilter,
			    false,
			    $querySelect,
			    [ 'nTopCount' => $limit ]
		    );
	    }
        else
        {
	        $queryFilter = [
			    'IBLOCK_ID' => $iblockId,
			    'ACTIVE' => 'Y',
			    'ACTIVE_DATE' => 'Y',
		    ];
		    $queryFilter[] = $filter;

		    $querySelect = [
			    $primaryField,
			    $searchField,
		    ];

			$result = \CIBlockElement::GetList(
				[],
				$queryFilter,
				false,
				[ 'nTopCount' => $limit ],
				$querySelect
			);
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
		$result = [];

		if ($dbQuery === null) { return $result; }

		$valueField = isset($field['AUTOCOMPLETE_PRIMARY']) ? $field['AUTOCOMPLETE_PRIMARY'] : $field['ID'];
		$titleField = isset($field['AUTOCOMPLETE_FIELD']) ? $field['AUTOCOMPLETE_FIELD'] : $field['ID'];

		while ($row = $dbQuery->fetch())
		{
			$itemValue = isset($row[$valueField]) ? trim($row[$valueField]) : '';
			$itemTitle = isset($row[$titleField]) ? trim($row[$titleField]) : '';

			if ($itemValue !== '' && $itemTitle !== '')
			{
				if ($field['TYPE'] === Market\Export\Entity\Data::TYPE_IBLOCK_SECTION)
				{
					$displayValue = str_repeat('.', $row['DEPTH_LEVEL'] - 1) . $itemTitle;
				}
				else if ($itemTitle !== $itemValue)
				{
					$displayValue = '[' . $itemValue . '] ' . $itemTitle;
				}
				else
				{
					$displayValue = $itemValue;
				}

				$result[] = [
					'ID' => $itemValue,
					'VALUE' => $displayValue,
				];
			}
		}

		return $result;
	}

	protected function isAutocompleteQueryMatchType($dataType, $query)
	{
		switch ($dataType)
		{
			case Market\Export\Entity\Data::TYPE_NUMBER:
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

    protected function buildQueryFilter($filter)
	{
		$rulesKeys = $this->getFilterRuleKeys();
		$rulesKeysMap = array_flip($rulesKeys);
		$rulesFilter = $this->extractFilterItems($filter, $rulesKeysMap);
		$configuredRules = $this->makeFilterRules($rulesFilter);
		$rules = $configuredRules + $this->getFilterDefaultRules();
		$appliedRules = [];
		$result = [];

		foreach ($filter as $filterItem)
		{
			if (isset($rulesKeysMap[$filterItem['FIELD']])) { continue; }

			$compare = $filterItem['COMPARE'];
			$field = $filterItem['FIELD'];
			$value = $filterItem['VALUE'];

			if ($field === 'IBLOCK_SECTION_ID' || $field === 'SECTION_ID')
			{
				$field = 'SECTION_ID';
				$isCompareEqual = (Market\Data\TextString::getPosition($compare, '!') !== 0);
				$compare = $isCompareEqual ? '' : '!';

				if (empty($value))
				{
					// nothing
				}
				else if ($isCompareEqual)
				{
					$result['INCLUDE_SUBSECTIONS'] = 'Y';
					$result += $rules['SECTION_ACTIVE'];
					$appliedRules['SECTION_ACTIVE'] = true;
				}
				else
				{
					$result += $rules['SECTION_ACTIVE'];
					$appliedRules['SECTION_ACTIVE'] = true;

					$sectionMargins = $this->loadSectionMargin($value);

					if ($this->hasSectionChild($sectionMargins))
					{
						$field = 'SUBSECTION';
						$value = $sectionMargins;
					}
				}
			}
			else if ($field === 'STRICT_SECTION_ID')
			{
				$field = 'SECTION_ID';
				$isCompareEqual = (Market\Data\TextString::getPosition($compare, '!') !== 0);
				$compare = $isCompareEqual ? '' : '!';

				$result += $rules['SECTION_ACTIVE'];
				$appliedRules['SECTION_ACTIVE'] = true;
			}
			else if ($field === 'CHECK_PERMISSIONS')
			{
				$isCompareEqual = (Market\Data\TextString::getPosition($compare, '!') !== 0);
				$compare = '';

				if (!$isCompareEqual)
				{
					$value = ($value === 'Y' ? 'N' : 'Y');
				}

				if ($value === 'Y' && !isset($result['PERMISSIONS_BY']))
				{
					$result['PERMISSIONS_BY'] = 0;
				}
			}
			else if ($field === 'PERMISSIONS_BY')
			{
				$isCompareEqual = (Market\Data\TextString::getPosition($compare, '!') !== 0);

				if (!$isCompareEqual) { continue; }

				$compare = '';
				$result['CHECK_PERMISSIONS'] = 'Y';
			}
			else if ($field === 'TIMESTAMP_X' || Market\Data\TextString::getPosition($field, 'DATE_') === 0)
			{
				$value = $this->convertQueryFilterDate($value);
			}

			$this->pushQueryFilter($result, $compare, $field, $value);
        }

		$this->applyFilterRules($result, array_diff_key($configuredRules, $appliedRules));

        return $result;
	}

	protected function extractFilterItems($filter, array $keysMap)
	{
		$result = [];

		foreach ($filter as $item)
		{
			$field = $item['FIELD'];

			if (!isset($keysMap[$field])) { continue; }

			if (!isset($result[$field]))
			{
				$result[$field] = [];
			}

			$result[$field][] = $item;
		}

		return $result;
	}

	protected function makeFilterRules($rulesFilter)
	{
		$result = [];

		if (isset($rulesFilter['ACTIVE']))
		{
			$result['ACTIVE'] = $this->makeFilterRuleActive($rulesFilter['ACTIVE']);
		}

		if (isset($rulesFilter['SECTION_ACTIVE']))
		{
			$result['SECTION_ACTIVE'] = $this->makeFilterRuleSectionActive($rulesFilter['SECTION_ACTIVE']);
		}

		return $result;
	}

	protected function getFilterDefaultRules()
	{
		return [
			'ACTIVE' => [ 'ACTIVE' => 'Y', 'ACTIVE_DATE' => 'Y' ],
			'SECTION_ACTIVE' => [ 'SECTION_GLOBAL_ACTIVE' => 'Y' ],
		];
	}

	protected function getFilterRuleKeys()
	{
		return [
			'ACTIVE',
			'SECTION_ACTIVE',
		];
	}

	protected function makeFilterRuleActive($filter)
	{
		$result = [];

		foreach ($filter as $item)
		{
			$isCompareEqual = (Market\Data\TextString::getPosition($item['COMPARE'], '!') !== 0);
			$compare = $isCompareEqual ? '' : '!';

			foreach ((array)$item['VALUE'] as $value)
			{
				if ($value === 'ANY') { continue; }

				$field = $item['FIELD'];

				if ($value === 'DATE')
				{
					$this->pushQueryFilter($result, $compare, $field, 'Y');

					$field = 'ACTIVE_DATE';
					$value = 'Y';
				}

				$this->pushQueryFilter($result, $compare, $field, $value);
			}
		}

		return $result;
	}

	protected function makeFilterRuleSectionActive($filter)
	{
		$result = [];

		foreach ($filter as $item)
		{
			$isCompareEqual = (Market\Data\TextString::getPosition($item['COMPARE'], '!') !== 0);
			$compare = $isCompareEqual ? '' : '!';

			foreach ((array)$item['VALUE'] as $value)
			{
				$field = null;

				if ($value === 'Y')
				{
					$field = 'SECTION_ACTIVE';
				}
				else if ($value === 'GLOBAL')
				{
					$field = 'SECTION_GLOBAL_ACTIVE';
					$value = 'Y';
				}

				if ($field !== null)
				{
					$this->pushQueryFilter($result, $compare, $field, $value);
				}
			}
		}

		return $result;
	}

	protected function applyFilterRules(&$result, $rules)
	{
		if (empty($rules)) { return; }

		$rulesFilter = [];

		foreach ($rules as $rule)
		{
			if (empty($rule)) { continue; }

			$rulesFilter += $rule;
		}

		if (!empty($rulesFilter))
		{
			$result = $rulesFilter + $result;
		}
	}

	protected function convertQueryFilterDate($value)
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

				$newValue[] = ConvertTimeStamp($valueDate->getTimestamp(), 'FULL');
			}
			else
			{
				$newValue[] = $valueItem;
			}
		}

		return $isMultiple ? $newValue : reset($newValue);
	}

	protected function getContextIblockId(array $context = [])
    {
        return $context['IBLOCK_ID'];
    }

    protected function preloadFieldValues(&$elementList, $select, $context = null)
	{
		$fieldsByType = [
			'F' => [
				'PREVIEW_PICTURE',
				'DETAIL_PICTURE'
			]
		];

		foreach ($fieldsByType as $type => $fields)
		{
			$selectedFields = array_intersect($fields, $select);

			if (empty($selectedFields)) { continue; }

			$valueMap = $this->collectValuesMap($elementList, $selectedFields);
			$valueList = array_keys($valueMap);

			foreach (array_chunk($valueList, 500) as $valueChunk)
			{
				switch ($type)
				{
					case 'F':
						$query = \CFile::GetList([], ['@ID' => $valueChunk]);

						while ($row = $query->Fetch())
						{
							if (!isset($valueMap[$row['ID']])) { continue; }

							$preloadValue = \CFile::GetFileSRC($row);

							foreach ($valueMap[$row['ID']] as list($elementId, $field))
							{
								$element = &$elementList[$elementId];

								if (!isset($element['PRELOAD']))
								{
									$element['PRELOAD'] = [];
								}

								$element['PRELOAD'][$field] = $preloadValue;

								unset($element);
							}
						}
					break;
				}
			}
		}
	}

	protected function resolveInvalidValues(&$elementList, $select, $context = null)
	{
		$fieldsByType = [
			Market\Export\Entity\Data::TYPE_IBLOCK_SECTION => [
				'IBLOCK_SECTION_ID',
			],
		];

		foreach ($fieldsByType as $type => $fields)
		{
			$selectedFields = array_intersect($fields, $select);

			if (empty($selectedFields)) { continue; }

			$valueMap = $this->collectValuesMap($elementList, $selectedFields);
			$valueList = array_keys($valueMap);

			foreach (array_chunk($valueList, 500) as $valueChunk)
			{
				$invalidValues = $this->getInvalidValues($type, $valueChunk, $context);

				if (empty($invalidValues)) { continue; }

				$invalidValuesMap = array_intersect_key($valueMap, $invalidValues);
				$searchIds = [];

				foreach ($invalidValuesMap as $invalidItems)
				{
					foreach ($invalidItems as list($elementId))
					{
						$searchId = $elementList[$elementId]['ID'];
						$searchIds[$searchId] = true;
					}
				}

				$validValues = $this->resolveValidValues($type, array_keys($searchIds), $context);

				foreach ($invalidValuesMap as $invalidItems)
				{
					foreach ($invalidItems as list($elementId, $field))
					{
						$searchId = $elementList[$elementId]['ID'];
						$validValue = isset($validValues[$searchId]) ? $validValues[$searchId] : null;

						$elementList[$elementId]['~' . $field] = $elementList[$elementId][$field];
						$elementList[$elementId][$field] = $validValue;
					}
				}
			}
		}
	}

	protected function getInvalidValues($type, $valueList, $context = null)
	{
		if (!isset($this->invalidValuesCache[$type]))
		{
			$this->invalidValuesCache[$type] = [];
		}

		$valueMap = array_flip($valueList);
		$searchValues = array_diff_key($valueMap, $this->invalidValuesCache[$type]);
		$result = array_intersect_key($this->invalidValuesCache[$type], $valueMap);

		if (!empty($searchValues))
		{
			$fetchedValues = $this->fetchInvalidValues($type, array_keys($searchValues), $context);

			if (!empty($fetchedValues))
			{
				$result += $fetchedValues;
				$this->invalidValuesCache[$type] += $fetchedValues;
			}
		}

		return array_filter($result);
	}

	protected function fetchInvalidValues($type, $valueList, $context = null)
	{
		$result = [];

		switch ($type)
		{
			case Market\Export\Entity\Data::TYPE_IBLOCK_SECTION:
				$iblockId = $this->getContextIblockId($context);

				if ($iblockId > 0)
				{
					$result = array_fill_keys($valueList, true);
					$filter = [
						'=IBLOCK_ID' => $iblockId,
						'=ID' => $valueList,
					];
					$filter += $this->getSectionRule();

					$filter = array_merge($filter, $this->sectionFilter);

					$query = Iblock\SectionTable::getList([
						'filter' => $filter,
						'select' => [ 'ID' ],
					]);

					while ($row = $query->fetch())
					{
						$result[$row['ID']] = false;
					}
				}
			break;
		}

		return $result;
	}

	protected function resolveValidValues($type, $elementIds, $context = null)
	{
		$result = [];

		switch ($type)
		{
			case Market\Export\Entity\Data::TYPE_IBLOCK_SECTION:
				// load element to sections link

				$usedSections = [];
				$elementToSectionsMap = [];

				$query = \CIBlockElement::GetElementGroups($elementIds, true, [
					'ID',
					'GLOBAL_ACTIVE',
					'IBLOCK_ELEMENT_ID'
				]);

				while ($row = $query->fetch())
				{
					if ($row['GLOBAL_ACTIVE'] !== 'Y') { continue; }

					$elementId = (int)$row['IBLOCK_ELEMENT_ID'];
					$sectionId = (int)$row['ID'];

					if (!isset($elementToSectionsMap[$elementId]))
					{
						$elementToSectionsMap[$elementId] = [];
					}

					$elementToSectionsMap[$elementId][] = $sectionId;
					$usedSections[$sectionId] = true;
				}

				// unset invalid sections

				if (!empty($usedSections) && !empty($this->sectionFilter))
				{
					$invalidSections = $this->getInvalidValues($type, array_keys($usedSections), $context);
					$usedSections = array_diff_key($usedSections, $invalidSections);
				}

				// resolve one element section

				foreach ($elementToSectionsMap as $elementId => $sectionIds)
				{
					$elementResult = null;

					foreach ($sectionIds as $sectionId)
					{
						if (!isset($usedSections[$sectionId])) { continue; }

						if ($elementResult === null || $sectionId > $elementResult)
						{
							$elementResult = $sectionId;
						}
					}

					$result[$elementId] = $elementResult;
				}
			break;
		}

		return $result;
	}

	protected function collectValuesMap($elementList, $select)
	{
		$valueMap = [];

		foreach ($elementList as $elementId => $element)
		{
			foreach ($select as $field)
			{
				if (!empty($element[$field]))
				{
					$value = $element[$field];

					if (!isset($valueMap[$value])) { $valueMap[$value] = []; }

					$valueMap[$value][] = [ $elementId, $field ];
				}
			}
		}

		return $valueMap;
	}

	protected function getFieldValues($element, $select, $parent = null, $context = null)
	{
		$result = [];
		$hasPreload = isset($element['PRELOAD']);

		foreach ($select as $fieldName)
		{
			$fieldValue = null;

			if (isset($element[$fieldName]))
			{
				$fieldValue = $element[$fieldName];

				switch ($fieldName)
				{
					case 'PREVIEW_PICTURE':
					case 'DETAIL_PICTURE':
						if ($hasPreload)
						{
							$fieldValue = isset($element['PRELOAD'][$fieldName]) ? $element['PRELOAD'][$fieldName] : null;
						}
						else
						{
							$fieldValue = \CFile::GetPath($fieldValue);
						}
					break;

					case 'CANONICAL_PAGE_URL':
					case 'DETAIL_PAGE_URL':
						$useOriginValues = ($fieldName === 'CANONICAL_PAGE_URL');

						if (isset($parent['DETAIL_PAGE_URL']) && Market\Data\TextString::getPosition($fieldValue, '#PRODUCT_URL#') !== false)
						{
							$parentTemplate = ($useOriginValues && isset($parent['~DETAIL_PAGE_URL']) ? $parent['~DETAIL_PAGE_URL'] : $parent['DETAIL_PAGE_URL']);
							$parentUrl = $this->makeDetailUrl($parentTemplate, $parent, $context, $useOriginValues);

							$fieldValue = str_replace('#PRODUCT_URL#', $parentUrl, $fieldValue);
						}

						$fieldValue = $this->makeDetailUrl($fieldValue, $element, $context, $useOriginValues);
					break;
				}
			}

			$result[$fieldName] = $fieldValue;
		}

		return $result;
	}

	protected function getLangPrefix()
	{
		return 'IBLOCK_ELEMENT_FIELD_';
	}

	protected function loadSectionMargin($sectionIds)
	{
		$result = [];

		$querySections = Iblock\SectionTable::getList([
			'filter' => [
				'=ID' => $sectionIds
			],
			'select' => [
				'ID',
				'LEFT_MARGIN',
				'RIGHT_MARGIN'
			]
		]);

		while ($section = $querySections->fetch())
		{
			$result[] = [
				(int)$section['LEFT_MARGIN'],
				(int)$section['RIGHT_MARGIN']
			];
		}

		return $result;
	}

	protected function hasSectionChild($sectionMargins)
	{
		$result = false;

		foreach ($sectionMargins as $sectionMargin)
		{
			if ($sectionMargin[1] > $sectionMargin[0] + 1)
			{
				$result = true;
				break;
			}
		}

		return $result;
	}

	protected function makeDetailUrl($template, $element, $context, $useOriginSection = false)
	{
		if ($useOriginSection && isset($element['~IBLOCK_SECTION_ID']))
		{
			$element['IBLOCK_SECTION_ID'] = $element['~IBLOCK_SECTION_ID'];
		}

		$template = $this->replaceUrlSiteVariables($template, $context);

		return \CIBlock::ReplaceDetailUrl($template, $element, false, 'E');
	}

	protected function replaceUrlSiteVariables($url, $context)
	{
		$result = $url;
		$replaces = Market\Data\Site::getUrlVariables($context['SITE_ID']);

		if ($replaces !== false)
		{
			$result = str_replace($replaces['from'], $replaces['to'], $result);
		}

		return $result;
	}
}
