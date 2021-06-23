<?php

namespace Yandex\Market\Export\Entity\Iblock\Property\Feature;

use Yandex\Market;
use Bitrix\Main;
use Bitrix\Iblock;

Main\Localization\Loc::loadMessages(__FILE__);

class Source extends Market\Export\Entity\Reference\Source
{
	protected $featurePropertyFields;
	protected $featurePropertyMap;
	protected $featurePropertyData;

	public function getFields(array $context = [])
	{
		$result = [];

		if (!$this->loadFeature()) { return $result; }

		$internalFields = $this->getInternalFields();
		$langPrefix = $this->getLangPrefix();

		foreach ($this->getFeatures($context) as $feature)
		{
			if (!isset($feature['MODULE_ID'], $feature['FEATURE_ID'])) { continue; }

			$featureFieldKey = $feature['MODULE_ID'] . '.' . $feature['FEATURE_ID'];
			$featureName = !empty($feature['FEATURE_NAME']) ? $feature['FEATURE_NAME'] : $feature['FEATURE_ID'];
			$featureTitleLangKey = $langPrefix . 'FEATURE_' . Market\Data\TextString::toUpper($feature['MODULE_ID']) . '_' . Market\Data\TextString::toUpper($feature['FEATURE_ID']);
			$featureTitle = Market\Config::getLang($featureTitleLangKey, null, $featureName);

			foreach ($internalFields as $internalField)
			{
				$field = $internalField;
				$field['ID'] = $featureFieldKey . '.' . $internalField['ID'];
				$field['VALUE'] = str_replace('#FEATURE_NAME#', $featureTitle, $internalField['VALUE']);

				$result[] = $field;
			}
		}

		return $result;
	}

	public function initializeQueryContext($select, &$queryContext, &$sourceSelect)
	{
		if (!$this->loadFeature()) { return; }

		$sourceTypesMap = array_filter([
			Market\Export\Entity\Manager::TYPE_IBLOCK_ELEMENT_PROPERTY => $queryContext['IBLOCK_ID'],
			Market\Export\Entity\Manager::TYPE_IBLOCK_OFFER_PROPERTY => $queryContext['HAS_OFFER'] ? $queryContext['OFFER_IBLOCK_ID'] : null,
		]);

		$featureFields = $this->splitFeatureSelect($select);
		$featureProperties = $this->getFeatureProperties($featureFields, $sourceTypesMap);
		$requestedProperties = $this->getRequestedProperties($sourceSelect, $sourceTypesMap);
		$featureProperties = $this->excludeRequestedProperties($featureProperties, $requestedProperties);

		$this->extendSourceSelectByValues($sourceSelect, $featureFields, $featureProperties);

		$this->featurePropertyFields = $featureFields;
		$this->featurePropertyMap = $featureProperties;
		$this->featurePropertyData = $this->resolvePropertyDataValues($featureFields, $featureProperties);
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

				if (!empty($elementValues))
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
				foreach ($multipleKeys as $multipleKey => $dummy)
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
		$langPrefix = $this->getLangPrefix();

		return [
			[
				'ID' => 'DISPLAY_VALUE',
				'VALUE' => Market\Config::getLang($langPrefix . 'FIELD_DISPLAY_VALUE', null, '#FEATURE_NAME# (DISPLAY_VALUE)'),
				'TYPE' => Market\Export\Entity\Data::TYPE_STRING,
				'FILTERABLE' => false,
				'SELECTABLE' => true,
				'TAG' => [ 'param' ],
			],
			[
				'ID' => 'NAME',
				'VALUE' => Market\Config::getLang($langPrefix . 'FIELD_NAME', null, '#FEATURE_NAME# (NAME)'),
				'TYPE' => Market\Export\Entity\Data::TYPE_STRING,
				'FILTERABLE' => false,
				'SELECTABLE' => true,
				'TAG' => [ 'param.name' ],
			],
			[
				'ID' => 'UNIT',
				'VALUE' => Market\Config::getLang($langPrefix . 'FIELD_UNIT', null, '#FEATURE_NAME# (UNIT)'),
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

	protected function getFeatureProperties($featureFields, $sourceTypesMap)
	{
		$result = [];

		if (!empty($sourceTypesMap))
		{
			$iblockIds = array_values($sourceTypesMap);
			$iblockToSourceTypeMap = array_flip($sourceTypesMap);

			foreach ($featureFields as $featureFieldKey => $featureField)
			{
				$result[$featureFieldKey] = [];

				$queryProperties = Iblock\PropertyFeatureTable::getList([
					'select' => [
						'IBLOCK_PROPERTY_ID' => 'PROPERTY.ID',
						'IBLOCK_PROPERTY_IBLOCK_ID' => 'PROPERTY.IBLOCK_ID',
					],
					'filter' => [
						'=MODULE_ID' => $featureField['MODULE_ID'],
						'=FEATURE_ID' => $featureField['FEATURE_ID'],
						'=IS_ENABLED' => 'Y',
						'=PROPERTY.IBLOCK_ID' => $iblockIds,
						'=PROPERTY.ACTIVE' => 'Y',
					],
					'order' => [
						'PROPERTY.SORT' => 'ASC',
						'PROPERTY.ID' => 'ASC',
					],
				]);

				while ($property = $queryProperties->fetch())
				{
					$iblockId = (int)$property['IBLOCK_PROPERTY_IBLOCK_ID'];
					$sourceType = $iblockToSourceTypeMap[$iblockId];

					if (!isset($result[$featureFieldKey][$sourceType]))
					{
						$result[$featureFieldKey][$sourceType] = [];
					}

					$result[$featureFieldKey][$sourceType][] = (int)$property['IBLOCK_PROPERTY_ID'];
				}
			}
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

	protected function getRequestedProperties($sourceSelect, $sourceTypesMap)
	{
		$sourceTypes = array_keys($sourceTypesMap);
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

	protected function loadFeature()
	{
		return (
			Main\Loader::includeModule('iblock')
			&& class_exists(Iblock\Model\PropertyFeature::class)
			&& Iblock\Model\PropertyFeature::isEnabledFeatures()
		);
	}

	protected function getFeatures(array $context)
	{
		$result = $this->getIblockFeatures($context);
		$result = $this->unsetSiblingServiceFeatures($result, $context);
		$result = $this->sortFeatures($result);

		return $result;
	}

	protected function getIblockFeatures($context)
	{
		$property = $this->getFirstFeaturesProperty($context);

		if ($property !== null)
		{
			$result = Iblock\Model\PropertyFeature::getPropertyFeatureList($property);
		}
		else
		{
			$result = [];
		}

		return $result;
	}

	protected function unsetSiblingServiceFeatures($features, array $context)
	{
		$moduleId = Market\Config::getModuleName();
		$contextServiceName = $context['EXPORT_SERVICE'];
		$langPrefix = $this->getLangPrefix();

		foreach ($features as $featureKey => &$feature)
		{
			if ($feature['MODULE_ID'] !== $moduleId) { continue; }

			$isValid = false;
			$featureNameWithoutPrefix = str_replace(Market\Ui\Iblock\PropertyFeature::FEATURE_ID_PREFIX, '', $feature['FEATURE_ID']);
			$serviceName = Market\Data\TextString::toLower($featureNameWithoutPrefix);
			$service = null;

			if (
				$serviceName === Market\Ui\Service\Manager::TYPE_COMMON
				|| Market\Ui\Service\Manager::isExists($serviceName)
			)
			{
				$service = Market\Ui\Service\Manager::getInstance($serviceName);
				$isValid = ($service->isInverted() !== in_array($contextServiceName, $service->getExportServices(), true));
			}

			if ($isValid && $service !== null)
			{
				$feature['FEATURE_NAME'] = Market\Config::getLang(
					$langPrefix . 'SELF_FEATURE',
					[ '#SERVICE#' => $service->getTitle('GENITIVE') ],
					$feature['FEATURE_NAME']
				);
			}
			else
			{
				unset($features[$featureKey]);
			}
		}
		unset($feature);

		return $features;
	}

	protected function sortFeatures($features)
	{
		uasort($features, function($featureA, $featureB) {
			$features = [
				'A' => $featureA,
				'B' => $featureB,
			];
			$sorts = array_fill_keys(array_keys($features), 500);

			foreach ($features as $featureKey => $feature)
			{
				if ($feature['MODULE_ID'] === Market\Config::getModuleName())
				{
					$sorts[$featureKey] = 1;
				}
				else if ($feature['MODULE_ID'] === 'iblock' && $feature['FEATURE_ID'] === Iblock\Model\PropertyFeature::FEATURE_ID_DETAIL_PAGE_SHOW)
				{
					$sorts[$featureKey] = 2;
				}
				else if ($feature['MODULE_ID'] === 'iblock' && $feature['FEATURE_ID'] === Iblock\Model\PropertyFeature::FEATURE_ID_LIST_PAGE_SHOW)
				{
					$sorts[$featureKey] = 3;
				}
			}

			if ($sorts['A'] === $sorts['B']) { return 0; }

			return $sorts['A'] < $sorts['B'] ? -1 : 1;
		});

		return $features;
	}

	protected function getFirstFeaturesProperty(array $context)
	{
		$parametersVariants = [
			$this->getFeaturesPropertyParametersForOffersTree($context),
			$this->getFeaturesPropertyParametersForOffersBasket($context),
			$this->getFeaturesPropertyParametersForCatalog($context),
			$this->getFeaturesPropertyParametersForElements($context),
		];
		$result = null;

		foreach ($parametersVariants as $parametersVariant)
		{
			if ($parametersVariant === null) { continue; }

			$parametersVariant['limit'] = 1;

			$query = Iblock\PropertyTable::getList($parametersVariant);

			if ($row = $query->fetch())
			{
				$result = $row;
				break;
			}
		}

		return $result;
	}

	protected function getFeaturesPropertyParametersForOffersTree(array $context)
	{
		$result = null;

		if (isset($context['OFFER_IBLOCK_ID']) && (int)$context['OFFER_IBLOCK_ID'] > 0)
		{
			$result = [
				'filter' => [
					'=IBLOCK_ID' => $context['OFFER_IBLOCK_ID'],
					'=MULTIPLE' => 'N',
					[
						'LOGIC' => 'OR',
						[
							'=PROPERTY_TYPE' => Iblock\PropertyTable::TYPE_LIST,
						],
						[
							'=PROPERTY_TYPE' => Iblock\PropertyTable::TYPE_ELEMENT,
							'!=USER_TYPE' => \CIBlockPropertySKU::USER_TYPE,
						],
						[
							'=PROPERTY_TYPE' => Iblock\PropertyTable::TYPE_STRING,
							'=USER_TYPE' => 'directory',
						],
					]
				],
			];
		}

		return $result;
	}

	protected function getFeaturesPropertyParametersForOffersBasket(array $context)
	{
		$result = null;

		if (isset($context['OFFER_IBLOCK_ID']) && (int)$context['OFFER_IBLOCK_ID'] > 0)
		{
			$result = [
				'filter' => [
					'=IBLOCK_ID' => $context['OFFER_IBLOCK_ID'],
					'!=PROPERTY_TYPE' => Iblock\PropertyTable::TYPE_FILE,
				],
			];
		}

		return $result;
	}

	protected function getFeaturesPropertyParametersForCatalog(array $context)
	{
		$result = null;

		if (isset($context['IBLOCK_ID']) && (int)$context['IBLOCK_ID'] > 0)
		{
			$result = [
				'filter' => [
					'=IBLOCK_ID' => $context['IBLOCK_ID'],
					[
						'LOGIC' => 'OR',
						[
							'=MULTIPLE' => 'N',
							'=PROPERTY_TYPE' => [
								Iblock\PropertyTable::TYPE_ELEMENT,
								Iblock\PropertyTable::TYPE_LIST,
							],
						],
						[
							'=MULTIPLE' => 'Y',
							'=PROPERTY_TYPE' => [
								Iblock\PropertyTable::TYPE_ELEMENT,
								Iblock\PropertyTable::TYPE_SECTION,
								Iblock\PropertyTable::TYPE_LIST,
								Iblock\PropertyTable::TYPE_NUMBER,
								Iblock\PropertyTable::TYPE_STRING,
							],
						],
					]
				],
			];
		}

		return $result;
	}

	protected function getFeaturesPropertyParametersForElements($context)
	{
		$result = null;

		if (isset($context['IBLOCK_ID']) && (int)$context['IBLOCK_ID'] > 0)
		{
			$result = [
				'filter' => [ '=IBLOCK_ID' => $context['IBLOCK_ID'] ],
			];
		}

		return $result;
	}
}