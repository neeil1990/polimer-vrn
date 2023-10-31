<?php

namespace Yandex\Market\Export\Entity\Iblock\Property\Feature;

use Yandex\Market;
use Bitrix\Iblock;

class Source extends Market\Export\Entity\Reference\Source
{
	use Market\Reference\Concerns\HasMessage;

	protected $featurePropertyFields;
	protected $featurePropertyMap;
	protected $featurePropertyData;

	protected $setFactory = [];

	public function getTitle()
	{
		return self::getMessage('TITLE');
	}

	public function getFields(array $context = [])
	{
		$internalFields = $this->getInternalFields();
		$result = [];

		/** @var Set\Set $feature */
		foreach ($this->getFactory($context) as $feature)
		{
			foreach ($internalFields as $internalField)
			{
				$field = $internalField;
				$field['ID'] = $feature->key() . '.' . $internalField['ID'];
				$field['VALUE'] = str_replace('#FEATURE_NAME#', $feature->title(), $internalField['VALUE']);

				if ($feature->deprecated())
				{
					$field['DEPRECATED'] = true;
				}

				$result[] = $field;
			}
		}

		return $result;
	}

	public function initializeQueryContext($select, &$queryContext, &$sourceSelect)
	{
		$featureFields = $this->splitFeatureSelect($select);
		$featureProperties = $this->getFeatureProperties($featureFields, $queryContext);
		$requestedProperties = $this->getRequestedProperties($sourceSelect, $this->usedFeaturePropertiesSources($featureProperties));
		$featureProperties = $this->excludeRequestedProperties($featureProperties, $requestedProperties);

		$this->extendSourceSelectByValues($sourceSelect, $featureFields, $featureProperties);

		$this->featurePropertyFields = $featureFields;
		$this->featurePropertyMap = $featureProperties;
		$this->featurePropertyData = $this->resolvePropertyDataValues($featureFields, $featureProperties);
	}

	protected function usedFeaturePropertiesSources(array $featureProperties)
	{
		$sources = [];

		foreach ($featureProperties as $featureSources)
		{
			$sources += $featureSources;
		}

		return array_keys($sources);
	}

	public function getElementListValues($elementList, $parentList, $selectFields, $queryContext, $sourceValues)
	{
		$result = [];

		if ($this->featurePropertyFields === null) { return $result; }

		foreach ($this->featurePropertyFields as $featureFieldKey => $featureField)
		{
			if (!isset($this->featurePropertyMap[$featureFieldKey])) { continue; }

			$propertiesMap = $this->featurePropertyMap[$featureFieldKey];

			foreach ($elementList as $elementId => $element)
			{
				$elementValues = [];

				foreach ($propertiesMap as $sourceType => $propertyIds)
				{
					if (!isset($sourceValues[$elementId][$sourceType])) { continue; }

					foreach ($propertyIds as $propertyId)
					{
						$values = $this->getElementPropertyValues($sourceValues[$elementId][$sourceType], $propertyId, $featureField['VALUE_FIELDS']);

						if ($values === null) { continue; }

						if (isset($this->featurePropertyData[$featureFieldKey][$propertyId]))
						{
							$values += $this->featurePropertyData[$featureFieldKey][$propertyId];
						}

						$this->fillElementValues($elementValues, $values, $featureFieldKey);
					}
				}

				if (empty($elementValues)) { continue; }

				if (isset($result[$elementId]))
				{
					$result[$elementId] += $elementValues;
				}
				else
				{
					$result[$elementId] = $elementValues;
				}
			}
		}

		return $result;
	}

	protected function getElementPropertyValues($propertyValues, $propertyId, $select)
	{
		$hasAny = false;
		$result = array_fill_keys($select, null);

		foreach ($select as $field)
		{
			$suffix = $field === 'DISPLAY_VALUE' ? '' : '.' . $field;
			$fieldKey = $propertyId . $suffix;

			if (isset($propertyValues[$fieldKey]))
			{
				$hasAny = true;
				$result[$field] = $propertyValues[$fieldKey];
			}
		}

		return $hasAny ? $result : null;
	}

	protected function fillElementValues(&$elementValues, $values, $keyPrefix)
	{
		$hasMultiple = false;
		$multipleFields = [];
		$multipleKeys = [];

		foreach ($values as $key => $value)
		{
			if (is_array($value))
			{
				$hasMultiple = true;
				$multipleFields[$key] = true;
				$multipleKeys += array_flip(array_keys($value));
			}
		}

		foreach ($values as $resultField => $value)
		{
			$valueKey = $keyPrefix . '.' . $resultField;

			if (!isset($elementValues[$valueKey]))
			{
				$elementValues[$valueKey] = [];
			}

			if (isset($multipleFields[$resultField]))
			{
				foreach ($multipleKeys as $multipleKey => $dummy)
				{
					$elementValues[$valueKey][] = isset($value[$multipleKey]) ? $value[$multipleKey] : null;
				}
			}
			else if ($hasMultiple)
			{
				foreach ($multipleKeys as $ignored)
				{
					$elementValues[$valueKey][] = $value;
				}
			}
			else
			{
				$elementValues[$valueKey][] = $value;
			}
		}
	}

	protected function getInternalFields()
	{
		return [
			[
				'ID' => 'DISPLAY_VALUE',
				'VALUE' => self::getMessage('FIELD_DISPLAY_VALUE', null, '#FEATURE_NAME# (DISPLAY_VALUE)'),
				'TYPE' => Market\Export\Entity\Data::TYPE_STRING,
				'FILTERABLE' => false,
				'SELECTABLE' => true,
				'TAG' => [ 'param' ],
			],
			[
				'ID' => 'NAME',
				'VALUE' => self::getMessage('FIELD_NAME', null, '#FEATURE_NAME# (NAME)'),
				'TYPE' => Market\Export\Entity\Data::TYPE_STRING,
				'FILTERABLE' => false,
				'SELECTABLE' => true,
				'TAG' => [ 'param.name' ],
			],
			[
				'ID' => 'UNIT',
				'VALUE' => self::getMessage('FIELD_UNIT', null, '#FEATURE_NAME# (UNIT)'),
				'TYPE' => Market\Export\Entity\Data::TYPE_STRING,
				'FILTERABLE' => false,
				'SELECTABLE' => true,
				'TAG' => [ 'param.unit' ],
			],
		];
	}

	protected function splitFeatureSelect($select)
	{
		$result = [];
		$dataFields = $this->getPropertyDataFields();
		$dataFieldsMap = array_flip($dataFields);

		foreach ($select as $field)
		{
			$isMatch = preg_match('/^([a-z.]+)\.(\w+)\.(\w+)$/', $field, $matches);

			if (!$isMatch) { continue; }

			list(, $moduleId, $featureId, $internalField) = $matches;

			$key = $moduleId . '.' . $featureId;

			if (!isset($result[$key]))
			{
				$result[$key] = [
					'MODULE_ID' => $moduleId,
					'FEATURE_ID' => $featureId,
					'VALUE_FIELDS' => [],
					'DATA_FIELDS' => [],
				];
			}

			if (isset($dataFieldsMap[$internalField]))
			{
				$result[$key]['DATA_FIELDS'][] = $internalField;
			}
			else
			{
				$result[$key]['VALUE_FIELDS'][] = $internalField;
			}
		}

		return $result;
	}

	protected function getPropertyDataFields()
	{
		return [
			'NAME',
			'UNIT',
		];
	}

	protected function getFeatureProperties($featureFields, $queryContext)
	{
		$result = [];

		/** @var Set\Set $feature */
		foreach ($this->getFactory($queryContext) as $feature)
		{
			$featureKey = $feature->key();

			if (!isset($featureFields[$featureKey])) { continue; }

			$result[$featureKey] = $feature->properties();
		}

		return $result;
	}

	protected function excludeRequestedProperties($featureProperties, $excludePropertyIds)
	{
		foreach ($featureProperties as &$propertiesBySource)
		{
			foreach ($propertiesBySource as $sourceType => $propertyIds)
			{
				$filteredPropertyIds = array_diff($propertyIds, $excludePropertyIds);

				if (!empty($filteredPropertyIds))
				{
					$propertiesBySource[$sourceType] = $filteredPropertyIds;
				}
				else
				{
					unset($propertiesBySource[$sourceType]);
				}
			}
		}
		unset($propertiesBySource);

		return $featureProperties;
	}

	protected function getRequestedProperties($sourceSelect, $sourceTypes)
	{
		$result = [];

		foreach ($sourceTypes as $sourceType)
		{
			if (!isset($sourceSelect[$sourceType])) { continue; }

			foreach ($sourceSelect[$sourceType] as $sourceField)
			{
				$dotPosition = Market\Data\TextString::getPosition($sourceField, '.');
				$propertyId = $dotPosition === false
					? (int)$sourceField
					: (int)Market\Data\TextString::getSubstring($sourceField, 0, $dotPosition);

				if (!in_array($propertyId, $result, true))
				{
					$result[] = $propertyId;
				}
			}
		}

		return $result;
	}

	protected function extendSourceSelectByValues(&$sourceSelect, $featureFields, $featureProperties)
	{
		foreach ($featureProperties as $featureFieldKey => $propertiesBySourceType)
		{
			if (!isset($featureFields[$featureFieldKey])) { continue; }

			$featureField = $featureFields[$featureFieldKey];

			foreach ($propertiesBySourceType as $sourceType => $propertyIds)
			{
				if (!isset($sourceSelect[$sourceType]))
				{
					$sourceSelect[$sourceType] = [];
				}

				foreach ($featureField['VALUE_FIELDS'] as $valueField)
				{
					$suffix = $valueField === 'DISPLAY_VALUE' ? '' : '.' . $valueField;

					foreach ($propertyIds as $propertyId)
					{
						$sourceSelect[$sourceType][] = $propertyId . $suffix;
					}
				}
			}
		}
	}

	protected function resolvePropertyDataValues($featureFields, $featureProperties)
	{
		$result = [];
		$propertyRows = $this->loadPropertyDataRows($featureFields, $featureProperties);

		foreach ($featureFields as $featureFieldKey => $featureField)
		{
			if (empty($featureField['DATA_FIELDS'])) { continue; }
			if (empty($featureProperties[$featureFieldKey])) { continue; }

			$propertiesBySourceType = $featureProperties[$featureFieldKey];
			$isNeedUnit = in_array('UNIT', $featureField['DATA_FIELDS'], true);
			$result[$featureFieldKey] = [];

			foreach ($propertiesBySourceType as $propertyIds)
			{
				foreach ($propertyIds as $propertyId)
				{
					if (!isset($propertyRows[$propertyId])) { continue; }

					$propertyRow = $propertyRows[$propertyId];

					if ($isNeedUnit)
					{
						list($name, $unit) = $this->extractPropertyDataUnit($propertyRow);

						$propertyValues = [
							'NAME' => $name,
							'UNIT' => $unit,
						];
					}
					else
					{
						$propertyValues = [
							'NAME' => $propertyRow['NAME'],
						];
					}

					$result[$featureFieldKey][$propertyId] = $propertyValues;
				}
			}
		}

		return $result;
	}

	protected function loadPropertyDataRows($featureFields, $featureProperties)
	{
		$dataSelectMap = [];
		$propertyIdsMap = [];
		$result = [];

		foreach ($featureFields as $featureFieldKey => $featureField)
		{
			if (empty($featureField['DATA_FIELDS'])) { continue; }
			if (empty($featureProperties[$featureFieldKey])) { continue; }

			$dataSelectMap += array_flip($featureField['DATA_FIELDS']);

			foreach ($featureProperties[$featureFieldKey] as $propertyIds)
			{
				$propertyIdsMap += array_flip($propertyIds);
			}
		}

		if (!empty($dataSelectMap) && !empty($propertyIdsMap))
		{
			$dataSelectWithoutCalculated = array_diff_key($dataSelectMap, [ 'UNIT' => true ]);

			$query = Iblock\PropertyTable::getList([
				'filter' => [
					'=ID' => array_keys($propertyIdsMap),
				],
				'select' => array_merge(
					[ 'ID' ],
					array_keys($dataSelectWithoutCalculated)
				)
			]);

			while ($row = $query->fetch())
			{
				$propertyId = (int)$row['ID'];

				$result[$propertyId] = $row;
			}
		}

		return $result;
	}

	protected function extractPropertyDataUnit($property)
	{
		$name = trim($property['NAME']);
		$unit = null;
		$unitRegexp = '(?<unit>\S{1,3})';

		if (preg_match('/^(?<name>.*?)\(' . $unitRegexp . '\)$/', $name, $matches))
		{
			$name = trim($matches['name']);
			$unit = trim($matches['unit']);
		}
		else if (preg_match('/^(?<name>.*?),\s*' . $unitRegexp . '$/', $name, $matches))
		{
			$name = trim($matches['name']);
			$unit = trim($matches['unit']);
		}
		else if (preg_match('/^' . $unitRegexp . '$/', trim($property['HINT']), $matches))
		{
			$unit = $matches['unit'];
		}

		return [$name, $unit];
	}

	protected function getLangPrefix()
	{
		return 'IBLOCK_PROPERTY_FEATURE_';
	}

	protected function getFactory(array $context)
	{
		$iblockId = (int)$context['IBLOCK_ID'];

		if (!isset($this->setFactory[$iblockId]))
		{
			$this->setFactory[$iblockId] = new Set\Factory($context);
		}

		return $this->setFactory[$iblockId];
	}
}