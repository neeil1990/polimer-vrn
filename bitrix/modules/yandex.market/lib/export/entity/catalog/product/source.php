<?php

namespace Yandex\Market\Export\Entity\Catalog\Product;

use Yandex\Market;
use Yandex\Market\Export\Entity\Fetcher;
use Bitrix\Main;
use Bitrix\Catalog;

Main\Localization\Loc::loadMessages(__FILE__);

class Source extends Market\Export\Entity\Reference\Source
{
	protected $measures = [];

	public function getQuerySelect($select)
	{
		$result = [
			'CATALOG' => []
		];

		if (!Market\Export\Entity\Catalog\Provider::useCatalogShortFields())
		{
			$select = $this->extendQuerySelect($select);
			$splitFields = $this->getSplitFields();
			$systemFields = $this->getSystemFieldsMap();
			$externalFields = $this->getExternalFields();

			foreach ($select as $fieldName)
			{
				if (isset($splitFields[$fieldName]))
				{
					foreach ($splitFields[$fieldName] as $partFieldName)
					{
						$result['CATALOG'][] = $this->getFieldFullName($partFieldName);
					}
				}
				else if (!isset($systemFields[$fieldName]) && !in_array($fieldName, $externalFields, true))
				{
					$result['CATALOG'][] = $this->getFieldFullName($fieldName);
				}
			}
		}

		return $result;
	}

	public function isFilterable()
	{
		return true;
	}

	public function getQueryFilter($filter, $select)
	{
		$result = [
			'ELEMENT' => [],
			'CATALOG' => []
		];

		foreach ($filter as $filterItem)
		{
			$fieldFullName = $this->getFieldFullName($filterItem['FIELD']);
			$sourceKey = 'CATALOG';

			if ($filterItem['FIELD'] === 'TYPE')
			{
				$sourceKey = 'ELEMENT';
			}

            $this->pushQueryFilter($result[$sourceKey], $filterItem['COMPARE'], $fieldFullName, $filterItem['VALUE']);
		}

		return $result;
	}

	public function getElementListValues($elementList, $parentList, $select, $queryContext, $sourceValues)
	{
		$result = [];

		if (!empty($elementList) && Main\Loader::includeModule('catalog'))
		{
			$useInternalLoading = Market\Export\Entity\Catalog\Provider::useCatalogShortFields();
			$externalSelect = array_intersect($select, $this->getExternalFields($useInternalLoading));
			$externalSelectMap = array_flip($externalSelect);
			$systemSelectMap = array_intersect(array_flip($this->getSystemFieldsMap()), $select);
			$systemSelect = array_flip($systemSelectMap);
			$internalSelect = $useInternalLoading ? $this->extendQuerySelect($select) : [];
			$internalSelect = array_merge($internalSelect, $systemSelect);
			$internalSelect = array_diff($internalSelect, $externalSelect);
			$internalSelect = $this->extendInternalSelect($internalSelect, $externalSelect);
			$internalSelectMap = array_flip($internalSelect) + $systemSelect;

			$internalData = $this->loadInternalData($elementList, $internalSelect);
			$externalData = $this->loadExternalData($elementList, $externalSelect, $internalData);

			foreach ($elementList as $elementId => $element)
			{
				$result[$elementId] = [];
				$elementInternalData = isset($internalData[$elementId]) ? $internalData[$elementId] : null;

				foreach ($select as $fieldName)
				{
					if (isset($internalSelectMap[$fieldName]))
					{
						$result[$elementId][$fieldName] = $this->getDisplayValue($elementInternalData, $fieldName, $queryContext);
					}
					else if (isset($externalSelectMap[$fieldName]))
					{
						$result[$elementId][$fieldName] = isset($externalData[$fieldName][$elementId])
							? $externalData[$fieldName][$elementId]
							: null;
					}
					else
					{
						$result[$elementId][$fieldName] = $this->getDisplayValue($element, $fieldName, $queryContext);
					}
				}
			}
		}

		return $result;
	}

	public function getFields(array $context = [])
	{
		$result = [];

		if ($context['HAS_CATALOG'])
		{
			$result = $this->buildFieldsDescription([
				'WEIGHT' => [
					'TYPE' => Market\Export\Entity\Data::TYPE_NUMBER
				],
				'LENGTH' => [
					'TYPE' => Market\Export\Entity\Data::TYPE_NUMBER
				],
				'HEIGHT' => [
					'TYPE' => Market\Export\Entity\Data::TYPE_NUMBER
				],
				'WIDTH' => [
					'TYPE' => Market\Export\Entity\Data::TYPE_NUMBER
				],
				'YM_SIZE' => [
					'TYPE' => Market\Export\Entity\Data::TYPE_STRING,
					'FILTERABLE' => false
				],
				'AVAILABLE' => [
					'TYPE' => Market\Export\Entity\Data::TYPE_BOOLEAN
				],
				'QUANTITY' => [
					'TYPE' => Market\Export\Entity\Data::TYPE_NUMBER
				],
				'MEASURE' => [
					'TYPE' => Market\Export\Entity\Data::TYPE_STRING
				],
				'MEASURE_TITLE' => [
					'TYPE' => Market\Export\Entity\Data::TYPE_STRING
				],
				'MEASURE_SYMBOL' => [
					'TYPE' => Market\Export\Entity\Data::TYPE_STRING
				],
				'MEASURE_RATIO' => [
					'TYPE' => Market\Export\Entity\Data::TYPE_NUMBER,
					'FILTERABLE' => false
				],
				'PURCHASING_PRICE_RUR' => [
					'TYPE' => Market\Export\Entity\Data::TYPE_NUMBER,
					'FILTERABLE' => false
				],
				'PURCHASING_PRICE' => [
					'TYPE' => Market\Export\Entity\Data::TYPE_NUMBER
				],
				'PURCHASING_CURRENCY' => [
					'TYPE' => Market\Export\Entity\Data::TYPE_CURRENCY
				],
				'VAT' => [
					'TYPE' => Market\Export\Entity\Data::TYPE_NUMBER,
					'FILTERABLE' => false,
				],
				'TYPE' => [
					'TYPE' => Market\Export\Entity\Data::TYPE_ENUM,
					'SELECTABLE' => false
				]
			]);

			if (Main\Config\Option::get('catalog', 'default_use_store_control') === 'Y')
			{
				$storeControlFields = $this->buildFieldsDescription([
					'BARCODE' => [
						'TYPE' => Market\Export\Entity\Data::TYPE_STRING,
					],
				]);

				$result = array_merge($result, $storeControlFields);
			}

			$systemFieldsMap = $this->getSystemFieldsMap();
			$systemFieldsMap = array_flip($systemFieldsMap);

			foreach ($this->getUserFieldFetcher()->getFields() as $field)
			{
				if (isset($systemFieldsMap[$field['ID']]))
				{
					$field['ID'] = $systemFieldsMap[$field['ID']];
				}

				$result[] = $field + [
					'FILTERABLE' => false,
					'SELECTABLE' => true,
				];
			}
		}

		return $result;
	}

	public function getFieldEnum($field, array $context = [])
	{
		$result = null;

		switch ($field['ID'])
		{
			case 'TYPE':
				$result = $this->getCatalogProductTypes();
			break;

			default:
				$result = parent::getFieldEnum($field, $context);
			break;
		}

		return $result;
	}

	protected function getLangPrefix()
	{
		return 'CATALOG_PRODUCT_';
	}

	protected function getCatalogProductTypes()
	{
		$result = [];

		if (Main\Loader::includeModule('catalog'))
		{
			$types = [
				'TYPE_PRODUCT',
				'TYPE_SET',
				'TYPE_SKU'
			];

			foreach ($types as $type)
			{
				$constantName = '\CCatalogProduct::' . $type;

				if (defined($constantName))
				{
					$result[] = [
						'ID' => constant($constantName),
						'VALUE' => Market\Config::getLang($this->getLangPrefix() . 'FIELD_TYPE_ENUM_' . $type)
					];
				}
			}
		}

		return $result;
	}

	protected function getDisplayValue($element, $fieldName, $context = null)
	{
		$result = null;

		if ($fieldName === 'YM_SIZE')
		{
			$keys = [ $this->getFieldFullName('LENGTH'), $this->getFieldFullName('WIDTH'), $this->getFieldFullName('HEIGHT') ];
			$values = [];
			$hasIsset = false;

			foreach ($keys as $key)
			{
				$value = 0;

				if (isset($element[$key]))
				{
					$value = (float)$element[$key];

					if ($value > 0)
					{
						$hasIsset = true;
					}
				}

				$values[] = $value;
			}

			if ($hasIsset)
			{
				$result = implode('/', $values);
			}
		}
		else if ($fieldName === 'PURCHASING_PRICE_RUR')
		{
			$elementKey = $this->getFieldFullName('PURCHASING_PRICE');

			if (isset($element[$elementKey]))
			{
				$price = (float)$element[$elementKey];
				$currency = (string)$element[$this->getFieldFullName('PURCHASING_CURRENCY')];
				$convertCurrency = Market\Data\Currency::getCurrency('RUB');

				if ($convertCurrency !== false && $currency !== $convertCurrency)
				{
					$price = Market\Data\Currency::convert($price, $currency, $convertCurrency);
					$currency = $convertCurrency;
				}

				$result = Market\Data\Currency::round($price, $currency);
			}
		}
		else
		{
			if (isset($element[$fieldName]))
			{
				$originalValue = $element[$fieldName];
			}
			else
			{
				$elementKey = $this->getFieldFullName($fieldName);
				$originalValue = (isset($element[$elementKey]) ? $element[$elementKey] : null);
			}

			if ($originalValue !== null)
			{
				switch ($fieldName)
				{
					case 'PURCHASING_PRICE':
						$price = (float)$originalValue;
						$currency = (string)$element[$this->getFieldFullName('PURCHASING_CURRENCY')];

						if (!empty($context['CONVERT_CURRENCY']))
						{
							$price = Market\Data\Currency::convert($price, $currency, $context['CONVERT_CURRENCY']);
							$currency = $context['CONVERT_CURRENCY'];
						}

						$result = Market\Data\Currency::round($price, $currency);
					break;

					case 'PURCHASING_CURRENCY':
						$result = !empty($context['CONVERT_CURRENCY']) ? $context['CONVERT_CURRENCY'] : $originalValue;
					break;

					case 'WEIGHT':
						if ((float)$originalValue > 0)
						{
							$result = $originalValue;
						}
					break;

					default:
						$result = $originalValue;
					break;
				}
			}
		}

		return $result;
	}

	protected function loadInternalData($elementList, $select)
	{
		$result = [];
		$entity = Catalog\ProductTable::getEntity();

		if (empty($select)) { return $result; }

		list($internalSelect, $referenceMap, $runtime) = $this->convertSelectToInternalFields($select, $entity);
		$systemFieldsMap = $this->getSystemFieldsMap();
		$needSystem = (count(array_intersect_key($systemFieldsMap, $select)) > 0);
		$internalSelect[] = 'ID';

		$query = Catalog\ProductTable::getList([
			'filter' => [ '=ID' => array_keys($elementList) ],
			'select' => $internalSelect,
			'runtime' => $runtime
		]);

		while ($row = $query->fetch())
		{
			if ($needSystem)
			{
				Catalog\Product\SystemField::convertRow($row);
			}

			foreach ($referenceMap as $selectName => $fieldName)
			{
				$row[$fieldName] = isset($row[$selectName]) ? $row[$selectName] : null;
			}

			$result[$row['ID']] = $row;
		}

		return $result;
	}

	protected function convertSelectToInternalFields($select, Main\Entity\Base $entity)
	{
		$querySelect = [];
		$referenceMap = [];
		$runtime = [];
		$referenceFields = $this->getReferenceFields();
		$splitFields = $this->getSplitFields();

		foreach ($select as $key => $field)
		{
			if (isset($splitFields[$field]))
			{
				foreach ($splitFields[$field] as $partField)
				{
					if ($entity->hasField($partField))
					{
						$querySelect[] = $partField;
					}
				}
			}
			else if (isset($referenceFields[$field]))
			{
				$reference = $referenceFields[$field];
				$selectName = 'YM_FIELD_' . $field;

				$querySelect[$selectName] = $reference[0] . '.' . $reference[1];
				$referenceMap[$selectName] = $field;

				if (!isset($runtime[$reference[0]]))
				{
					$runtime[$reference[0]] = $this->getRuntimeField($reference[0]);
				}
			}
			else if ($entity->hasField($field))
			{
				if (is_numeric($key))
				{
					$querySelect[] = $field;
				}
				else
				{
					$querySelect[$key] = $field;
				}
			}
		}

		return [ $querySelect, $referenceMap, $runtime ];
	}

	protected function loadExternalData($elementList, $externalSelect, $internalData = null)
	{
		$result = [];

		foreach ($externalSelect as $externalField)
		{
			$loadMap = $this->getExternalFieldLoadMap($elementList, $externalField, $internalData);

			if (!empty($loadMap))
			{
				$result[$externalField] = $this->loadExternalField($loadMap, $externalField);
			}
			else
			{
				$result[$externalField] = [];
			}
		}

		return $result;
	}

	protected function getExternalFieldLoadMap($elementList, $fieldName, $internalData = null)
	{
		switch ($fieldName)
		{
			case 'MEASURE_TITLE':
			case 'MEASURE_SYMBOL':
				$result = [];

				foreach ($elementList as $elementId => $element)
				{
					$measureId = null;

					if (isset($internalData[$elementId]['MEASURE']))
					{
						$measureId = (int)$internalData[$elementId]['MEASURE'];
					}
					else if (isset($element['CATALOG_MEASURE']))
					{
						$measureId = (int)$element['CATALOG_MEASURE'];
					}

					if ($measureId > 0)
					{
						if (!isset($result[$measureId]))
						{
							$result[$measureId] = [];
						}

						$result[$measureId][] = $elementId;
					}
				}
			break;

			case 'BARCODE':
				$result = [];

				foreach ($elementList as $elementId => $element)
				{
					$hasBarcodeMultiple = isset($internalData[$elementId]['BARCODE_MULTI'])
						? $internalData[$elementId]['BARCODE_MULTI'] === 'Y'
						: false;

					if (!$hasBarcodeMultiple)
					{
						$result[] = $elementId;
					}
				}
			break;

			default:
				$result = array_keys($elementList);
			break;
		}

		return $result;
	}

	protected function loadExternalField($loadMap, $field)
	{
		$result = [];

		switch ($field)
		{
			case 'MEASURE_TITLE':
			case 'MEASURE_SYMBOL':
				$measureIds = array_keys($loadMap);
				$measures = $this->getMeasures($measureIds);

				foreach ($measures as $measureId => $measure)
				{
					if ($measure === null || empty($measure[$field])) { continue; }

					$result += array_fill_keys($loadMap[$measureId], $measure[$field]);
				}
			break;

			case 'MEASURE_RATIO':
				$query = \CCatalogMeasureRatio::GetList(
					[],
					[ '@PRODUCT_ID' => $loadMap ],
					false,
					false,
					[ 'PRODUCT_ID', 'RATIO' ]
				);

				while ($row = $query->Fetch())
				{
					$result[$row['PRODUCT_ID']] = $row['RATIO'];
				}
			break;

			case 'BARCODE':
				$query = \CCatalogStoreBarCode::GetList(
					[],
					[ '@PRODUCT_ID' => $loadMap, '=STORE_ID' => 0 ],
					false,
					false,
					[ 'PRODUCT_ID', 'BARCODE' ]
				);

				while ($row = $query->Fetch())
				{
					if (!isset($result[$row['PRODUCT_ID']]))
					{
						$result[$row['PRODUCT_ID']] = [];
					}

					$result[$row['PRODUCT_ID']][] = $row['BARCODE'];
				}
			break;
		}

		return $result;
	}

	protected function getMeasures($ids)
	{
		$idsMap = array_flip($ids);
		$needLoad = array_diff_key($idsMap, $this->measures);

		if (!empty($needLoad))
		{
			$this->measures += $this->loadMeasures(array_keys($needLoad));
		}

		return array_intersect_key($this->measures, $idsMap);
	}

	protected function loadMeasures($ids)
	{
		$result = array_fill_keys($ids, null);

		$query = \CCatalogMeasure::getList(
			[],
			[ '=ID' => $ids ],
			false,
			false,
			[ 'ID', 'MEASURE_TITLE', 'SYMBOL_RUS' ]
		);

		while ($row = $query->Fetch())
		{
			$result[$row['ID']] = [
				'MEASURE_TITLE' => $row['MEASURE_TITLE'],
				'MEASURE_SYMBOL' => $row['SYMBOL_RUS'],
			];
		}

		return $result;
	}

	protected function getSplitFields()
	{
		return [
			'YM_SIZE' => [
				'LENGTH',
				'WIDTH',
				'HEIGHT',
			],
			'PURCHASING_PRICE_RUR' => [
				'PURCHASING_PRICE',
				'PURCHASING_CURRENCY',
			]
		];
	}

	protected function getReferenceFields()
	{
		return [
			'MEASURE_RATIO' => ['YM_MEASURE_RATIO', 'RATIO'],
			'VAT' => ['YM_VAT', 'RATE'],
		];
	}

	protected function getRuntimeField($key)
	{
		switch ($key)
		{
			case 'YM_MEASURE_RATIO':
				$result = new Main\Entity\ReferenceField(
					'YM_MEASURE_RATIO',
					'\Bitrix\Catalog\MeasureRatioTable',
					[ '=this.ID' => 'ref.PRODUCT_ID', '=ref.IS_DEFAULT' => [ '?', 'Y' ] ]
				);
			break;

			case 'YM_VAT';
				$result = new Main\Entity\ReferenceField(
					'YM_VAT',
					'\Bitrix\Catalog\VatTable',
					[ '=this.VAT_ID' => 'ref.ID' ]
				);
			break;

			default:
				throw new Main\SystemException('undefined reference field');
			break;
		}

		return $result;
	}

	protected function getSystemFieldsMap()
	{
		return method_exists(Catalog\Product\SystemField::class, 'getFieldList')
			? Catalog\Product\SystemField::getFieldList()
			: [];
	}

	protected function getExternalFields($useInternalLoading = false)
	{
		$result = [
			'BARCODE',
			'MEASURE_TITLE',
			'MEASURE_SYMBOL',
		];

		if (!$useInternalLoading)
		{
			$result[] = 'MEASURE_RATIO';
		}

		return $result;
	}

	protected function extendQuerySelect($select)
	{
		if (in_array('MEASURE_TITLE', $select, true) || in_array('MEASURE_SYMBOL', $select, true))
		{
			$select[] = 'MEASURE';
		}

		return $select;
	}

	protected function extendInternalSelect($internalSelect, $externalSelect)
	{
		if (in_array('BARCODE', $externalSelect, true))
		{
			$internalSelect[] = 'BARCODE_MULTI';
		}

		return $internalSelect;
	}

	protected function extractSelectFields($searchFields, $sourceFields)
	{
		$result = [];

		foreach ($searchFields as $searchField)
		{
			if (in_array($searchField, $sourceFields, true))
			{
				$result[] = $searchField;
			}
		}

		return $result;
	}

	protected function getFieldFullName($field)
	{
		if (!Market\Export\Entity\Catalog\Provider::useCatalogShortFields())
		{
			$result = 'CATALOG_' . $field;
		}
		else
		{
			$result = $field;
		}

		return $result;
	}

	protected function getUserFieldFetcher()
	{
		$type = defined(Catalog\ProductTable::class . '::USER_FIELD_ENTITY_ID')
			? Catalog\ProductTable::USER_FIELD_ENTITY_ID
			: 'PRODUCT';

		return new Fetcher\UserField($type);
	}
}