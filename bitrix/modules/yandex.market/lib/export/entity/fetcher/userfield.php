<?php

namespace Yandex\Market\Export\Entity\Fetcher;

use Bitrix\Main;
use Bitrix\Highloadblock;
use Yandex\Market;

class UserField
{
	protected $entityId;
	protected $fields;
	protected $isUserFieldMultipleCache = [];
	protected $requestedValues = [];
	protected $highloadDataClassCache = [];

	public function __construct($entityId)
	{
		global $USER_FIELD_MANAGER;

		$this->entityId = $entityId;
		$this->fields = $USER_FIELD_MANAGER->GetUserFields($this->entityId, 0, LANGUAGE_ID);
	}

	public function hasField($name)
	{
		if (isset($this->fields[$name]))
		{
			$result = true;
		}
		else
		{
			list($name, $code) = $this->splitName($name);

			$result = ((string)$code !== '' && isset($this->fields[$name]));
		}

		return $result;
	}

	public function getFields()
	{
		$result = [];

		foreach ($this->fields as $field)
		{
			$title = $field['EDIT_FORM_LABEL'] ?: $field['LIST_COLUMN_LABEL'] ?: $field['FIELD_NAME'];

			$result[] = [
				'ID' => $field['FIELD_NAME'],
				'VALUE' => $title,
				'TYPE' => Market\Export\Entity\Data::convertUserTypeToDataType($field['USER_TYPE_ID']),
			];

			if ($field['USER_TYPE_ID'] === 'enumeration')
			{
				$result[] = [
					'ID' => $field['FIELD_NAME'] . '.XML_ID',
					'VALUE' => sprintf('%s (%s)', $title, 'XML_ID'),
					'TYPE' => Market\Export\Entity\Data::TYPE_STRING,
				];
			}
		}

		return $result;
	}

	protected function getUserField($name)
	{
		if (isset($this->fields[$name]))
		{
			$result = $this->fields[$name];
		}
		else
		{
			list($partName) = $this->splitName($name);

			$result = isset($this->fields[$partName]) ? $this->fields[$partName] : null;
		}

		return $result;
	}

	protected function splitName($name)
	{
		return explode('.', $name, 2);
	}

	protected function isUserFieldMultiple($name)
	{
		if (!isset($this->isUserFieldMultipleCache[$name]))
		{
			$field = $this->getUserField($name);

			$this->isUserFieldMultipleCache[$name] = (isset($field['MULTIPLE']) && $field['MULTIPLE'] !== 'N');
		}

		return $this->isUserFieldMultipleCache[$name];
	}

	public function sanitizeValue($name, $value)
	{
		$field = $this->getUserField($name);

		if ($field['USER_TYPE_ID'] === 'file')
		{
			$result = $this->sanitizePositiveIntegerValue($value);
		}
		else
		{
			$result = $value;
		}

		return $result;
	}

	protected function sanitizePositiveIntegerValue($value)
	{
		if (is_array($value))
		{
			$result = array_filter($value, static function($item) {
				return (int)$item > 0;
			});
		}
		else if ((int)$value > 0)
		{
			$result = $value;
		}
		else
		{
			$result = null;
		}

		return $result;
	}

	public function requestValue($name, $value, array $exportIds)
	{
		$field = $this->getUserField($name);

		if ($field === null) { return; }

		$isFieldMultiple = $this->isUserFieldMultiple($name);
		$valueItems = $isFieldMultiple && is_array($value) ? $value : [ $value ];

		foreach ($valueItems as $valueItem)
		{
			if (Market\Utils\Value::isEmpty($valueItem)) { continue; }

			$valueItemKey = $this->makeValueKey($valueItem);

			if (!isset($this->requestedValues[$name]))
			{
				$this->requestedValues[$name] = [];
			}

			if (!isset($this->requestedValues[$name][$valueItemKey]))
			{
				$this->requestedValues[$name][$valueItemKey] = [
					'VALUE' => $valueItem,
					'EXPORT' => $exportIds,
				];
			}
			else if (!empty($exportIds))
			{
				array_push($this->requestedValues[$name][$valueItemKey]['EXPORT'], ...$exportIds);
			}
		}
	}

	public function releaseValues()
	{
		$this->requestedValues = [];
	}

	protected function makeValueKey($value)
	{
		if (is_scalar($value))
		{
			$result = (string)$value;
		}
		else if (is_object($value) && method_exists($value, '__toString'))
		{
			$result = (string)$value;
		}
		else
		{
			$result = serialize($value);
		}

		return $result;
	}

	public function extendResult(array $sectionValues)
	{
		$values = $this->collectValues();
		$result = $this->writeResult($sectionValues, $values);

		$this->releaseValues();

		return $result;
	}

	public function collectValues()
	{
		$result = [];

		foreach ($this->requestedValues as $fieldName => $exportMap)
		{
			list($name, $inner) = $this->splitName($fieldName);

			$field = $this->getUserField($name);

			if ($field === null) { continue; }

			$values = array_map(static function($exportData) { return $exportData['VALUE']; }, $exportMap);

			$result[$fieldName] = $this->convertFieldValues($field, $values, $inner);
		}

		return $result;
	}

	protected function convertFieldValues(array $field, array $values, $inner = null)
	{
		if ($field['USER_TYPE_ID'] === 'file')
		{
			$result = $this->convertFileValues($values);
		}
		elseif ($field['USER_TYPE_ID'] === 'enumeration')
		{
			$result = $this->convertEnumerationValues($values, $inner);
		}
		elseif ($field['USER_TYPE_ID'] === 'iblock_element')
		{
			$result = $this->convertIblockElementValues($values, $field, $inner);
		}
		elseif ($field['USER_TYPE_ID'] === 'hlblock')
		{
			$result = $this->convertHighloadValues($values, $field, $inner);
		}
		else
		{
			$result = $values;
		}

		return $result;
	}

	protected function convertFileValues(array $values)
	{
		Main\Type\Collection::normalizeArrayValuesByInt($values, false);

		if (empty($values)) { return []; }

		$result = [];
		$query = \CFile::GetList([], ['@ID' => $values]);

		while ($row = $query->Fetch())
		{
			$result[$row['ID']] = \CFile::GetFileSRC($row);
		}

		return $result;
	}

	protected function convertEnumerationValues(array $values, $inner = null)
	{
		Main\Type\Collection::normalizeArrayValuesByInt($values, false);

		if (empty($values)) { return []; }

		$field = $inner !== null ? $inner : 'VALUE';
		$result = [];
		$query = \CUserFieldEnum::GetList([], ['ID' => $values]);

		while ($row = $query->Fetch())
		{
			$result[$row['ID']] = $row[$field];
		}

		return $result;
	}

	protected function convertIblockElementValues(array $values, $context, $inner = null)
	{
		Main\Type\Collection::normalizeArrayValuesByInt($values, false);

		if (empty($values)) { return []; }

		$result = [];
		$field = $inner !== null ? $inner : 'NAME';
		$fieldProperty = $this->splitInnerProperty($field);
		$fieldSelect = [];
		$propertySelect = [];

		if ($fieldProperty !== null)
		{
			$propertySelect[] = $fieldProperty;
		}
		else
		{
			$fieldSelect[] = $field;
		}

		$query = \CIBlockElement::GetList(
			[],
			[ '=ID' => $values ],
			false,
			false,
			array_merge([ 'IBLOCK_ID', 'ID' ], $fieldSelect)
		);

		$rowsByIblock = [];

		while ($row = $query->Fetch())
		{
			$iblockId = (int)$row['IBLOCK_ID'];

			if (!isset($rowsByIblock[$iblockId]))
			{
				$rowsByIblock[$iblockId] = [];
			}

			$rowsByIblock[$iblockId][$row['ID']] = $row;
		}

		foreach ($rowsByIblock as $iblockId => $rows)
		{
			$innerContext = Market\Export\Entity\Iblock\Provider::getContext($iblockId);

			if (!empty($fieldSelect))
			{
				$fieldSource = Market\Export\Entity\Manager::getSource(
					Market\Export\Entity\Manager::TYPE_IBLOCK_ELEMENT_FIELD
				);

				$innerValues = $fieldSource->getElementListValues($rows, [], $fieldSelect, $innerContext, []);
				$result += $this->extendInnerValue($innerValues, $field);
			}

			if (!empty($propertySelect))
			{
				$propertySource = Market\Export\Entity\Manager::getSource(
					Market\Export\Entity\Manager::TYPE_IBLOCK_ELEMENT_PROPERTY
				);

				$innerValues = $propertySource->getElementListValues($rows, [], $propertySelect, $innerContext, []);
				$result += $this->extendInnerValue($innerValues, $fieldProperty);
			}
		}

		return $result;
	}

	protected function splitInnerProperty($field, $name = 'PROPERTY_')
	{
		$position = Market\Data\TextString::getPosition($field, $name);

		if ($position === false) { return null; }

		return Market\Data\TextString::getSubstring(
			$field,
			$position + Market\Data\TextString::getLength($name)
		);
	}

	protected function extendInnerValue($innerValues, $field)
	{
		$result = [];

		foreach ($innerValues as $id => $item)
		{
			$result[$id] = isset($item[$field]) ? $item[$field] : null;
		}

		return $result;
	}

	protected function convertHighloadValues(array $values, $context, $inner = null)
	{
		Main\Type\Collection::normalizeArrayValuesByInt($values, false);

		if (empty($values)) { return []; }

		$blockId = isset($context['SETTINGS']['HLBLOCK_ID']) ? (int)$context['SETTINGS']['HLBLOCK_ID'] : null;
		$fieldId = isset($context['SETTINGS']['HLFIELD_ID']) ? (int)$context['SETTINGS']['HLFIELD_ID'] : null;

		if ($blockId <= 0) { return []; }

		try
		{
			$entity = $this->getHighloadEntity($blockId);

			if ($entity === null) { return []; }

			if ($inner !== null)
			{
				$fieldCode = $inner;
			}
			else
			{
				$field = $this->getHighloadUserField($blockId, $fieldId);

				if ($field === null) { return []; }

				$fieldCode = $field['FIELD_NAME'];
			}

			$result = [];
			$dataClass = $entity->getDataClass();
			$query = $dataClass::getList([
				'filter' => [ '=ID' => $values ],
				'select' => [ 'ID', $fieldCode ],
			]);

			while ($row = $query->fetch())
			{
				if (!isset($row[$fieldCode])) { continue; }

				$result[$row['ID']] = $row[$fieldCode];
			}
		}
		catch (Main\DB\SqlException $exception)
		{
			$result = [];
		}

		return $result;
	}

	/**
	 * @param int $hlIblockId
	 *
	 * @return Main\Entity\Base|null
	 */
	protected function getHighloadEntity($hlIblockId)
	{
		$entity = false;
		$hlIblockId = (int)$hlIblockId;

		if ($hlIblockId <= 0)
		{
			// nothing
		}
		else if (isset($this->highloadDataClassCache[$hlIblockId]))
		{
			$entity = $this->highloadDataClassCache[$hlIblockId];
		}
		else if (Main\Loader::includeModule('highloadblock'))
		{
			$queryHighload = Highloadblock\HighloadBlockTable::getList([
				'filter' => ['=ID' => $hlIblockId],
			]);

			if ($highload = $queryHighload->fetch())
			{
				$entity = Highloadblock\HighloadBlockTable::compileEntity($highload);
			}

			$this->highloadDataClassCache[$hlIblockId] = $entity;
		}

		return $entity ?: null;
	}

	protected function getHighloadUserField($hlIblockId, $searchId)
	{
		$searchId = (int)$searchId;
		$result = null;

		foreach ($this->getHighloadUserFields($hlIblockId) as $field)
		{
			if ((int)$field['ID'] === $searchId)
			{
				$result = $field;
				break;
			}
		}

		return $result;
	}

	protected function getHighloadUserFields($hlIblockId)
	{
		global $USER_FIELD_MANAGER;

		return $USER_FIELD_MANAGER->GetUserFields('HLBLOCK_' . $hlIblockId);
	}

	protected function writeResult($sectionValues, $fieldsValues)
	{
		foreach ($fieldsValues as $fieldName => $values)
		{
			$isMultiple = $this->isUserFieldMultiple($fieldName);

			foreach ($values as $valueKey => $value)
			{
				if (!isset($this->requestedValues[$fieldName][$valueKey])) { continue; }

				$exportData = $this->requestedValues[$fieldName][$valueKey];

				foreach ($exportData['EXPORT'] as $exportId)
				{
					if (!$isMultiple)
					{
						$sectionValues[$exportId][$fieldName] = $value;
					}
					else
					{
						if (!isset($sectionValues[$exportId][$fieldName]))
						{
							$sectionValues[$exportId][$fieldName] = [];
						}

						$sectionValues[$exportId][$fieldName][] = $value;
					}
				}
			}
		}

		return $sectionValues;
	}
}