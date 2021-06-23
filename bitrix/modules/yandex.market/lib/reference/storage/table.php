<?php

namespace  Yandex\Market\Reference\Storage;

use Bitrix\Main;
use Yandex\Market;

abstract class Table extends Main\Entity\DataManager
{
	const BOOLEAN_Y = '1';
	const BOOLEAN_N = '0';

	public static function getClassName()
	{
		return '\\' . get_called_class();
	}

	public static function createIndexes(Main\DB\Connection $connection)
	{
		// nothing by default
	}

	public static function isValidData($data)
	{
		return true;
	}

	public static function addBatch(array $dataList, $updateOnDuplicate = false)
	{
		$result = new Main\Entity\AddResult();

		try
		{
			$entity = static::getEntity();
			$fields = $entity->getFields();
			$connection = $entity->getConnection();
			$helper = $connection->getSqlHelper();
			$tableName = $entity->getDBTableName();
			$sqlFieldPart = '';
			$sqlValuePart = '';
			$issetFieldsPart = false;
			$usedFields = [];

			foreach ($dataList as $data)
			{
				foreach ($data as $fieldName => $value)
				{
					if (!isset($fields[$fieldName]))
					{
						throw new Main\ArgumentException(sprintf(
							'%s Entity has no `%s` field.', $entity->getName(), $fieldName
						));
					}

					$field = $fields[$fieldName];

					$data[$fieldName] = $field->modifyValueBeforeSave($value, $data);

					if (!$issetFieldsPart)
					{
						$usedFields[] = $fieldName;
					}
				}

				$insert = $helper->prepareInsert($tableName, $data);

				if (!$issetFieldsPart)
				{
					$issetFieldsPart = true;
					$sqlFieldPart = $insert[0];
				}

				$sqlValuePart .= ($sqlValuePart !== '' ? ',' . PHP_EOL : '') . '(' . $insert[1] . ')';
			}

			if ($issetFieldsPart) // has data to insert
			{
				$insertRule = 'INSERT INTO';
				$duplicateSql = '';

				if ($updateOnDuplicate !== false)
				{
					if (is_array($updateOnDuplicate))
					{
						$duplicateFields = $updateOnDuplicate;
					}
					else
					{
						$tableFields = $connection->getTableFields($tableName);
						$primaryArray = $entity->getPrimaryArray();
						$primaryMap = array_flip($primaryArray);
						$duplicateFields = [];

						foreach ($usedFields as $fieldName)
						{
							if (!isset($primaryMap[$fieldName]) && isset($tableFields[$fieldName]))
							{
								$duplicateFields[] = $fieldName;
							}
						}
					}

					foreach ($duplicateFields as $fieldName)
					{
						$fieldNameQuoted = $helper->quote($fieldName);

						if ($duplicateSql !== '')
						{
							$duplicateSql .= ', ';
						}

						$duplicateSql .= $fieldNameQuoted . ' = VALUES(' . $fieldNameQuoted . ')';
					}

					if ($duplicateSql === '')
					{
						$insertRule = 'INSERT IGNORE INTO';
					}
					else
					{
						$duplicateSql =
							PHP_EOL . 'ON DUPLICATE KEY UPDATE'
							. PHP_EOL . $duplicateSql;
					}
				}

				$sql =
					$insertRule . ' ' . $tableName . '(' . $sqlFieldPart . ') ' .
					'VALUES ' . $sqlValuePart
					. $duplicateSql;

				$connection->queryExecute($sql);
			}
		}
		catch (\Exception $e)
		{
			// check result to avoid warning
			$result->isSuccess();

			throw $e;
		}

		return $result;
	}

	public static function add(array $data)
	{
		return self::addExtended($data);
	}

	public static function addExtended(array $data)
	{
		$reference = static::saveExtractReference($data);
		$data = static::convertNullForSave($data, true);
		$addResult = parent::add($data);

		if ($addResult->isSuccess())
		{
			static::saveApplyReference($addResult->getId(), $reference);
			static::onAfterSave($addResult->getId());
		}

		return $addResult;
	}

	public static function updateBatch($parameters, $data)
	{
		$result = new Main\Entity\UpdateResult();
		$query = static::createBatchQuery($parameters);

		try
		{
			$selectSql = $query->getQuery();

			if (preg_match('/^SELECT\s.*?\sFROM(\s.*?)(\s(?:LEFT |RIGHT |INNER )?JOIN\s.*?)?(\sWHERE\s.*?)?$/si', $selectSql, $match))
			{
				$entity = static::getEntity();
				$connection = $entity->getConnection();
				$helper = $connection->getSqlHelper();

				$tableName = $entity->getDBTableName();
				$tableAlias = $helper->quote($query->getInitAlias());

				$dataReplacedColumn = static::replaceFieldName($data);
				$update = $helper->prepareUpdate($tableName, $dataReplacedColumn);
				$update[0] = $tableAlias . '.' . str_replace(', ', ', ' . $tableAlias . '.', $update[0]);

				$sql = 'UPDATE ' . $match[1] . $match[2] . ' SET ' . $update[0] . $match[3];

				$connection->queryExecute($sql, $update[1]);

				$result->setAffectedRowsCount($connection);
			}
			else
			{
				throw new Main\SystemException('invalid updateBatch query');
			}
		}
		catch (\Exception $e)
		{
			// check result to avoid warning
			$result->isSuccess();

			throw $e;
		}

		return $result;
	}

	public static function update($primary, array $data)
	{
		return static::updateExtended($primary, $data);
	}

	public static function updateExtended($primary, array $data)
	{
		$reference = static::saveExtractReference($data);

		static::onBeforeSave($primary);

		if (!empty($data))
		{
			$data = static::convertNullForSave($data);
			$updateResult = parent::update($primary, $data);
		}
		else
		{
			$updateResult = new Main\Entity\UpdateResult();

			static::normalizePrimary($primary, $data);
			$updateResult->setPrimary($primary);
		}

		if ($updateResult->isSuccess())
		{
			static::saveApplyReference($updateResult->getId(), $reference);
			static::onAfterSave($updateResult->getId());
		}

		return $updateResult;
	}

	public static function deleteBatch($parameters)
	{
		$result = new Main\Entity\DeleteResult();
		$query = static::createBatchQuery($parameters);

		try
		{
			$selectSql = $query->getQuery();

			if (preg_match('/^SELECT\s.*?\s(FROM\s.*)$/si', $selectSql, $match))
			{
				$entity = static::getEntity();
				$connection = $entity->getConnection();
				$helper = $connection->getSqlHelper();
				$sql = 'DELETE ' . $helper->quote($query->getInitAlias()) . ' ' . $match[1];

				$connection->queryExecute($sql);
			}
			else
			{
				throw new Main\SystemException('invalid deleteBatch query');
			}
		}
		catch (\Exception $exception)
		{
			// check result to avoid warning
			$result->isSuccess();

			throw $exception;
		}

		return $result;
	}

	public static function delete($primary)
	{
		return self::deleteExtended($primary);
	}

	public static function deleteExtended($primary)
	{
		$id = static::getPrimaryId($primary);

		static::onBeforeRemove($id);

		$delResult = parent::delete($primary);

		// delete connected data
		if ($delResult->isSuccess())
		{
			static::deleteReference($id);
		}

		return $delResult;
	}
	
	public static function loadExternalReference($primaryList, $select = null, $isCopy = false)
	{
		$primaryList = (array)$primaryList;
		$result = [];

		if (!empty($primaryList))
		{
			$referenceList = static::getReference($primaryList);

			foreach ($referenceList as $field => $referenceConfig)
			{
				if (empty($select) || in_array($field, $select))
				{
					/** @var Table $referenceTable */
					$referenceTable = $referenceConfig['TABLE'];
					$referenceLinkField = $referenceConfig['LINK_FIELD'];
					$referenceEntity = $referenceTable::getEntity();
					$referencePrimaries = $referenceEntity->getPrimaryArray();
					$referencePrimariesWithoutLink = array_diff($referencePrimaries, (array)$referenceLinkField);
					$referencePrimaryField = count($referencePrimariesWithoutLink) === 1 ? reset($referencePrimariesWithoutLink) : 'ID';
					$isReferenceLinkFieldMultiple = is_array($referenceLinkField);
					$referenceFilter = static::makeReferenceLinkFilter($referenceConfig['LINK']);
					$rowList = [];

					$queryParams = [
						'filter' => $referenceFilter,
						'select' => [ '*' ]
					];

					if (isset($referenceConfig['ORDER']))
					{
						$queryParams['order'] = $referenceConfig['ORDER'];
					}

					if ($isReferenceLinkFieldMultiple)
					{
						$queryParams['select'] = array_merge($queryParams['select'], $referenceLinkField);
					}
					else
					{
						$queryParams['select'][] = $referenceLinkField;
					}

					$queryRows = $referenceTable::getList($queryParams);

					while ($row = $queryRows->fetch())
					{
						if ($isReferenceLinkFieldMultiple || isset($row[$referenceLinkField]))
						{
							$rowList[$row[$referencePrimaryField]] = $row;
						}
					}

					// load reference values

					if (!empty($rowList))
					{
						$externalDataList = $referenceTable::loadExternalReference(array_keys($rowList), null, $isCopy);

						foreach ($externalDataList as $rowId => $externalData)
						{
							$rowList[$rowId] += $externalData;
						}
					}

					// build result

					foreach ($rowList as $row)
					{
						$parentPrimary = '';

						if ($isReferenceLinkFieldMultiple)
						{
							foreach ($referenceLinkField as $referenceLinkFieldPart)
							{
								$parentPrimary .= ($parentPrimary === '' ? '' : ':') . $row[$referenceLinkFieldPart];

								if ($isCopy) { unset($row[$referenceLinkFieldPart]); }
							}
						}
						else
						{
							$parentPrimary = $row[$referenceLinkField];

							if ($isCopy) { unset($row[$referenceLinkField]); }
						}

						if (!isset($result[$parentPrimary]))
						{
							$result[$parentPrimary][$field] = [];
						}

						if ($isCopy) { unset($row['ID']); }

						$result[$parentPrimary][$field][] = $row;
					}
				}
			}
		}

		return $result;
	}

	public static function saveExtractReference(array &$data)
	{
		$referenceList = static::getReference();
		$result = [];

		foreach ($referenceList as $referenceField => $reference)
		{
			if (array_key_exists($referenceField, $data))
			{
				$result[$referenceField] = $data[$referenceField];
				unset($data[$referenceField]);
			}
		}

		return $result;
	}

	protected static function saveApplyReference($primary, $fields)
	{
		if (!empty($fields))
		{
			$referenceList = static::getReference($primary);

			foreach ($referenceList as $referenceField => $referenceConfig)
			{
				if (array_key_exists($referenceField, $fields))
				{
					/** @var Table $referenceTable */
					$referenceTable = $referenceConfig['TABLE'];
					$referenceEntity = $referenceTable::getEntity();
					$referencePrimaries = $referenceEntity->getPrimaryArray();
					$referencePrimariesWithoutLink = array_diff($referencePrimaries, (array)$referenceConfig['LINK_FIELD']);
					$referencePrimaryField = count($referencePrimariesWithoutLink) === 1 ? reset($referencePrimariesWithoutLink) : 'ID';
					$dataList = (array)$fields[$referenceField];
					$isValidDataList = is_array($dataList);
					$foundRowIds = [];

					// update exist and delete not present

					$idToDataKeyMap = [];

					if ($isValidDataList)
					{
						foreach ($dataList as $dataKey => $data)
						{
							if (!empty($data[$referencePrimaryField]) && $referenceTable::isValidData($data))
							{
								$rowId = $data[$referencePrimaryField];
								$idToDataKeyMap[$rowId] = $dataKey;
							}
						}
					}

					$queryExistRows = $referenceTable::getList([
						'filter' => static::makeReferenceLinkFilter($referenceConfig['LINK']),
						'select' => [ $referencePrimaryField ],
					]);

					while ($existRow = $queryExistRows->fetch())
					{
						$existRowFullPrimary = [];

						foreach ($referencePrimaries as $fieldName)
						{
							if (isset($existRow[$fieldName]))
							{
								$existRowFullPrimary[$fieldName] = $existRow[$fieldName];
							}
							else if (isset($referenceConfig['LINK'][$fieldName]))
							{
								$existRowFullPrimary[$fieldName] = $referenceConfig['LINK'][$fieldName];
							}
						}

						if (isset($idToDataKeyMap[$existRow[$referencePrimaryField]]))
						{
							$foundRowIds[$existRow[$referencePrimaryField]] = true;

							$dataKey = $idToDataKeyMap[$existRow[$referencePrimaryField]];
							$data = $dataList[$dataKey];

							unset($data[$referencePrimaryField]);

							$referenceTable::update($existRowFullPrimary, $data);
						}
						else
						{
							$referenceTable::delete($existRowFullPrimary);
						}
					}

					// add new

					if ($isValidDataList)
					{
						foreach ($dataList as $dataKey => $data)
						{
							$isValidForAdd = false;

							if (!$referenceTable::isValidData($data))
							{
								// nothing
							}
							else if (!isset($data[$referencePrimaryField]))
							{
								$isValidForAdd = true;
							}
							else if (!isset($foundRowIds[$data[$referencePrimaryField]]))
							{
								$isValidForAdd = true;

								if ($referencePrimaryField === $referenceEntity->getAutoIncrement())
								{
									unset($data[$referencePrimaryField]);
								}
							}

							if ($isValidForAdd)
							{
								if (isset($referenceConfig['LINK']))
								{
									$data += $referenceConfig['LINK'];
								}

								$referenceTable::add($data);
							}
						}
					}
				}
			}
		}
	}

	protected static function deleteReference($primary)
	{
		$referenceList = static::getReference($primary);

		foreach ($referenceList as $referenceField => $referenceConfig)
		{
			/** @var Table $referenceTable */
			$referenceTable = $referenceConfig['TABLE'];
			$referenceEntity = $referenceTable::getEntity();
			$referencePrimaries = $referenceEntity->getPrimaryArray();

			$queryExistRows = $referenceTable::getList([
				'filter' => static::makeReferenceLinkFilter($referenceConfig['LINK']),
				'select' => $referencePrimaries,
			]);

			while ($existRow = $queryExistRows->fetch())
			{
				$existPrimary = [];

				foreach ($referencePrimaries as $fieldName)
				{
					$existPrimary[$fieldName] = $existRow[$fieldName];
				}

				$referenceTable::delete($existPrimary);
			}
		}
	}

	protected static function onBeforeSave($primary)
	{
		// nothing
	}

	protected static function onAfterSave($primary)
	{
		// nothing
	}

	protected static function onBeforeRemove($primary)
    {
        // nothing
    }

	/**
	 * Ключ = Поле связи
	 * Значение = Масссив LINK_FIELD => Указатель на текущую сущность, LINK => Поля для связи, TABLE => Table::class
	 *
	 * @param int|int[]|null $primary
	 *
	 * @return array
	 */
	public static function getReference($primary = null)
	{
		return [];
	}

	public static function makeReferenceLinkFilter($link)
	{
		$result = [];

		foreach ($link as $field => $value)
		{
			if ($field === 'LOGIC')
			{
				$result[$field] = $value;
			}
			else if (!is_numeric($field))
			{
				$result['=' . $field] = $value;
			}
			else if (is_array($value))
			{
				$result[$field] = static::makeReferenceLinkFilter($value);
			}
			else
			{
				$result[$field] = $value;
			}
		}

		return $result;
	}

	/**
	 * Описание полей сущности в формате USER_FIELD_MANAGER
	 *
	 * @return array
	 */
	public static function getMapDescription()
	{
		global $USER_FIELD_MANAGER;

		$entity = static::getEntity();
		$referenceList = static::getReference();
		$result = [];

		/** @var Main\Entity\Field $field */
		foreach ($entity->getFields() as $field)
		{
			$fieldName = $field->getName();
			$userField = [];
			$userType = null;

			if (isset($result[$fieldName])) { continue; } // reference one to one conflict

			switch (true)
			{
				case ($field instanceof Main\Entity\EnumField): // enum

					$userType = 'enumeration';
					$userField['VALUES'] = [];
					$userField['SETTINGS'] = [
						'DEFAULT_VALUE' => $field->getDefaultValue()
					];

					foreach ($field->getValues() as $option)
					{
						$userField['VALUES'][] = [
							'ID' => $option,
							'VALUE' => static::getFieldEnumTitle($fieldName, $option, $field)
						];
					}

				break;

				case ($field instanceof Main\Entity\DateField): // date

					$userType = 'date';
					$userField['SETTINGS'] = [
						'DEFAULT_VALUE' => $field->getDefaultValue()
					];

				break;

				case ($field instanceof Main\Entity\DatetimeField): // datetime

					$userType = 'datetime';
					$userField['SETTINGS'] = [
						'DEFAULT_VALUE' => $field->getDefaultValue()
					];

				break;

				case ($field instanceof Main\Entity\IntegerField): // int

					$userType = 'integer';
					$userField['SETTINGS'] = [
						'DEFAULT_VALUE' => $field->getDefaultValue()
					];

				break;

				case ($field instanceof Main\Entity\FloatField): // double

					$userType = 'double';
					$userField['SETTINGS'] = [
						'DEFAULT_VALUE' => $field->getDefaultValue()
					];

				break;

				case ($field instanceof Main\Entity\StringField): // string
				case ($field instanceof Main\Entity\ExpressionField): // expression

					$userType = 'string';
					$userField['SETTINGS'] = [
						'DEFAULT_VALUE' => $field->getDefaultValue()
					];

				break;

				case ($field instanceof Main\Entity\BooleanField): // boolean

					$userType = 'boolean';
					$userField['SETTINGS'] = [
						'DEFAULT_VALUE' => $field->getDefaultValue()
					];

				break;

				case ($field instanceof Main\Entity\ReferenceField):

					$userType = 'reference';
					$userField['MULTIPLE'] = isset($referenceList[$fieldName]) ? 'Y' : 'N';
					$userField['SETTINGS']  = [
						'DATA_CLASS' => $field->getRefEntityName()
					];

				break;
			}

			if (!isset($userType)) { continue; }

			$userField += [
				'USER_TYPE' => Market\Ui\UserField\Manager::getUserType($userType),
				'FIELD_NAME' => $fieldName,
				'LIST_COLUMN_LABEL' => $field->getTitle(),
				'HELP_MESSAGE' => Main\Localization\Loc::getMessage($field->getLangCode() . '_HELP_MESSAGE'),
				'MANDATORY' => (method_exists($field, 'isRequired') && $field->isRequired() ? 'Y' : 'N'),
				'MULTIPLE' => 'N',
				'EDIT_IN_LIST' => (method_exists($field, 'isAutocomplete') && $field->isAutocomplete() ? 'N' : 'Y')
			];

			$result[$fieldName] = $userField;
		}

		return $result;
	}

	public static function migrate(Main\DB\Connection $connection)
	{
		$entity = static::getEntity();

		Market\Migration\StorageFacade::addNewFields($connection, $entity);
	}

	public static function getScalarMap()
	{
		$result = [];
		$map = static::getMap();

		foreach ($map as $field)
		{
			if ($field instanceof Main\Entity\ScalarField || $field instanceof Main\Entity\ExpressionField)
			{
				$result[] = $field->getName();
			}
		}

		return $result;
	}

	public static function getName()
	{
		$langKey = static::getLangKey();

		return Market\Config::getLang($langKey);
	}

	public static function getLangKey()
	{
		return 'UNKNOWN';
	}

	public static function getFieldEnumTitle($fieldName, $optionValue, Main\Entity\Field $field = null)
	{
		$result = null;

		if ($field === null)
		{
			$entity = static::getEntity();
			$field = $entity->getField($fieldName);
		}

		if ($field)
		{
			$fieldEnumLangKey = $field->getLangCode() . '_ENUM_';
			$optionValueLangKey = str_replace(['.', ' ', '-'], '_', $optionValue);
			$optionValueLangKey = Market\Data\TextString::toUpper($optionValueLangKey);

			$result = Main\Localization\Loc::getMessage($fieldEnumLangKey . $optionValueLangKey);
		}

		if ($result === null)
		{
			$result = $optionValue;
		}

		return $result;
	}

	protected static function extractReferenceField($fieldName)
	{
		$result = $fieldName;

		if (preg_match('/(?:this|ref)\.(.+)$/', $fieldName, $match))
		{
			$result = $match[1];
		}

		return $result;
	}

	protected static function createBatchQuery($parameters)
	{
		$query = static::query();

		foreach ($parameters as $param => $value)
		{
			switch($param)
			{
				case 'filter':
					$query->setFilter($value);
				break;

				case 'runtime':
					foreach ($value as $name => $fieldInfo)
					{
						$query->registerRuntimeField($name, $fieldInfo);
					}
				break;

				default:
					throw new Main\ArgumentException("Unknown parameter: ".$param, $param);
				break;
			}
		}

		return $query;
	}

	protected static function convertNullForSave($data, $isAdd = false)
	{
		$result = $data;

		foreach ($data as $fieldName => $fieldValue)
		{
			if ($fieldValue !== null)
			{
				// nothing
			}
			else if ($isAdd)
			{
				unset($result[$fieldName]);
			}
			else
			{
				$result[$fieldName] = '';
			}
		}

		return $result;
	}

	protected static function getPrimaryId($primary)
	{
		if (is_array($primary) && count($primary) === 1)
		{
			return end($primary);
		}

		return $primary;
	}
}