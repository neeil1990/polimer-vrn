<?php

namespace Yandex\Market\Export\Entity\Iblock\Element\Seo;

use Yandex\Market;
use Bitrix\Main;
use Bitrix\Iblock;

Main\Localization\Loc::loadMessages(__FILE__);

class Source extends Market\Export\Entity\Reference\Source
{
	public function isFilterable()
	{
		return true;
	}

	public function getQuerySelect($select)
	{
		$entityType = $this->getQueryEntityType();
		list($plainSelect) = $this->splitSelect($select);

		return [
			$entityType => $plainSelect,
		];
	}

	public function getQueryFilter($filter, $select)
	{
		$entityType = $this->getQueryEntityType();
		$queryFilter = $this->buildQueryFilter($filter);

		return [
			$entityType => $queryFilter,
		];
	}

	protected function buildQueryFilter($filter)
	{
		$result = [];

		foreach ($filter as $filterItem)
		{
			$this->pushQueryFilter($result, $filterItem['COMPARE'], $filterItem['FIELD'], $filterItem['VALUE']);
		}

		return $result;
	}

	protected function getQueryEntityType()
	{
		return 'ELEMENT';
	}

	public function getElementListValues($elementList, $parentList, $select, $queryContext, $sourceValues)
	{
		list($plainSelect, $seoSelect) = $this->splitSelect($select);
		$result = [];

		foreach ($elementList as $elementId => $element)
		{
			$parent = null;

			if (!isset($element['PARENT_ID']))
			{
				$parent = $element;
			}
			else if (isset($parentList[$element['PARENT_ID']])) // has parent element
			{
				$parent = $parentList[$element['PARENT_ID']];
			}

			if ($parent)
			{
				$result[$elementId] =
					$this->getPlainValues($parent, $plainSelect)
					+ $this->getSeoValues($parent, $seoSelect);
			}
		}

		return $result;
	}

	public function getFields(array $context = [])
	{
		return $this->buildFieldsDescription([
			'ELEMENT_META_TITLE' => [
				'TYPE' => Market\Export\Entity\Data::TYPE_STRING,
				'FILTERABLE' => false,
			],
			'ELEMENT_META_KEYWORDS' => [
				'TYPE' => Market\Export\Entity\Data::TYPE_STRING,
				'FILTERABLE' => false,
			],
			'ELEMENT_META_DESCRIPTION' => [
				'TYPE' => Market\Export\Entity\Data::TYPE_STRING,
				'FILTERABLE' => false,
			],
			'ELEMENT_PAGE_TITLE' => [
				'TYPE' => Market\Export\Entity\Data::TYPE_STRING,
				'FILTERABLE' => false,
			],
			'TAGS' => [
				'TYPE' => Market\Export\Entity\Data::TYPE_STRING,
				'FILTERABLE' => true,
			],
		]);
	}

	protected function getLangPrefix()
	{
		return 'IBLOCK_ELEMENT_SEO_';
	}

	protected function getPlainValues($element, $select)
	{
		$result = [];

		foreach ($select as $field)
		{
			if (!isset($element[$field])) { continue; }

			$result[$field] = $this->processPlainValue($field, $element[$field]);
		}

		return $result;
	}

	protected function processPlainValue($field, $value)
	{
		if ($field === 'TAGS')
		{
			$parts = explode(',', $value);
			$result = [];

			foreach ($parts as $part)
			{
				$part = trim($part);

				if ($part !== '')
				{
					$result[] = $part;
				}
			}
		}
		else
		{
			$result = $value;
		}

		return $result;
	}

	protected function getSeoValues($element, $select)
	{
		$result = [];

		if (!empty($select) && Main\Loader::includeModule('iblock'))
		{
			$provider = new Iblock\InheritedProperty\ElementValues($element["IBLOCK_ID"], $element["ID"]);
			$providerValues = $provider->getValues();

			foreach ($select as $fieldName)
			{
				$result[$fieldName] = isset($providerValues[$fieldName]) ? $providerValues[$fieldName] : null;
			}
		}

		return $result;
	}

	protected function splitSelect($select)
	{
		$plainFields = $this->getPlainFields();
		$plainSelect = array_intersect($select, $plainFields);
		$seoSelect = array_diff($select, $plainFields);

		return [ $plainSelect, $seoSelect ];
	}

	protected function getPlainFields()
	{
		return [
			'TAGS',
		];
	}
}