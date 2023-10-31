<?php

namespace Yandex\Market\Logger\Trading;

use Yandex\Market;
use Bitrix\Main;

Main\Localization\Loc::loadMessages(__FILE__);

class Table extends Market\Reference\Storage\Table
{
	public static function getTableName()
	{
		return 'yamarket_trading_log';
	}

	public static function createIndexes(Main\DB\Connection $connection)
	{
		$tableName = static::getTableName();

		$connection->createIndex($tableName, 'IX_' . $tableName . '_0', [ 'ENTITY_PARENT' ]);
		$connection->createIndex($tableName, 'IX_' . $tableName . '_1', [ 'ENTITY_TYPE', 'ENTITY_ID' ]);
		$connection->createIndex($tableName, 'IX_' . $tableName . '_2', [ 'AUDIT' ]);
	}

	public static function getUfId()
	{
		return 'YAMARKET_TRADING_LOG';
	}

	public static function getMap()
	{
		return [
			new Main\Entity\IntegerField('ID', [
				'autocomplete' => true,
				'primary' => true,
			]),
			new Main\Entity\DatetimeField('TIMESTAMP_X', [
				'required' => true,
			]),
			new Main\Entity\EnumField('LEVEL', [
				'required' => true,
				'values' => Market\Logger\Level::getVariants(),
			]),
			new Main\Entity\TextField('MESSAGE', [
				'required' => true,
			]),
			new Main\Entity\EnumField('AUDIT', [
				'values' => Audit::getVariants(),
			]),
			new Main\Entity\StringField('URL', [
				'size' => 255,
				'validation' => [__CLASS__, 'validateUrl'],
			]),
			new Main\Entity\StringField('ENTITY_TYPE', [
				'size' => 20,
				'validation' => [__CLASS__, 'validateEntityType'],
			]),
			new Main\Entity\IntegerField('ENTITY_PARENT'),
			new Main\Entity\StringField('ENTITY_ID', [
				'size' => 20,
				'validation' => [__CLASS__, 'validateEntityId'],
			]),
			new Main\Entity\TextField('CONTEXT', [
				'serialized' => true,
			]),
			new Main\Entity\TextField('TRACE', [
				'default_value' => '',
			]),

			// SETUP

			new Main\Entity\ExpressionField('SETUP_ID', '%s', 'ENTITY_PARENT'),

			new Main\Entity\ReferenceField('SETUP', Market\Trading\Setup\Table::class, [
				'=this.ENTITY_PARENT' => 'ref.ID'
			]),

			// ORDER

			new Main\Entity\ExpressionField(
				'ORDER_ID',
				'IF(%s = "' . Market\Trading\Entity\Registry::ENTITY_TYPE_ORDER . '", %s, NULL)',
				['ENTITY_TYPE', 'ENTITY_ID']
			),
		];
	}

	public static function migrate(Main\DB\Connection $connection)
	{
		static::migrateAudit($connection);
		static::migrateContext($connection);
	}

	protected static function migrateAudit(Main\DB\Connection $connection)
	{
		$entity = static::getEntity();

		Market\Migration\StorageFacade::updateFieldsLength($connection, $entity, [ 'AUDIT' ]);
	}

	protected static function migrateContext(Main\DB\Connection $connection)
	{
		$entity = static::getEntity();
		$storedTypes = Market\Migration\StorageFacade::getTableColumnTypes($connection, $entity);
		$columnName = 'CONTEXT';

		if (!isset($storedTypes[$columnName])) { return; }

		$sqlHelper = $connection->getSqlHelper();
		$field = $entity->getField($columnName);
		$fieldType = $sqlHelper->getColumnTypeByField($field);

		if (Market\Data\TextString::toLower($fieldType) === Market\Data\TextString::toLower($storedTypes[$columnName])) { return; }

		$connection->queryExecute(sprintf(
			'ALTER TABLE %s MODIFY COLUMN %s %s',
			$sqlHelper->quote($entity->getDBTableName()),
			$sqlHelper->quote($columnName),
			$fieldType
		));
	}

	public static function validateUrl()
	{
		return [
			new Main\Entity\Validator\Length(null, 255),
		];
	}

	public static function validateEntityType()
	{
		return [
			new Main\Entity\Validator\Length(null, 20),
		];
	}

	public static function validateEntityId()
	{
		return [
			new Main\Entity\Validator\Length(null, 20),
		];
	}

	public static function getMapDescription()
	{
		$result = parent::getMapDescription();
		$result['MESSAGE'] = static::extendMessageDescription($result['MESSAGE']);
		$result['LEVEL'] = static::extendLevelDescription($result['LEVEL']);
		$result['AUDIT'] = static::extendAuditDescription($result['AUDIT']);
		$result['SETUP'] = static::extendSetupDescription($result['SETUP']);
		$result['TRACE'] = static::extendTraceDescription($result['TRACE']);
		$result['CONTEXT'] = static::extendContextDescription($result['CONTEXT']);

		return $result;
	}

	protected static function extendMessageDescription($field)
	{
		$field['USER_TYPE'] = Market\Ui\UserField\Manager::getUserType('logMessage');

		return $field;
	}

	protected static function extendLevelDescription($field)
	{
		$field['USER_TYPE']['CLASS_NAME'] = Market\Ui\UserField\LogType::class;
		$allowedTypes = [
			Market\Logger\Level::ERROR => true,
			Market\Logger\Level::WARNING => true,
			Market\Logger\Level::INFO => true,
			Market\Logger\Level::DEBUG => true
		];

		foreach ($field['VALUES'] as $optionKey => &$option)
		{
			if (isset($allowedTypes[$option['ID']]))
			{
				$option['LOG_LEVEL'] = $option['ID'];
			}
			else
			{
				unset($field['VALUES'][$optionKey]);
			}
		}
		unset($option);

		return $field;
	}

	protected static function extendAuditDescription($field)
	{
		foreach ($field['VALUES'] as $optionKey => &$option)
		{
			$option['VALUE'] = Audit::getTitle($option['ID']);
		}
		unset($option);

		return $field;
	}

	protected static function extendSetupDescription($field)
	{
		$field['USER_TYPE']['CLASS_NAME'] = Market\Ui\UserField\SetupType::class;
		$field['SETTINGS']['EDIT_URL'] =
			Market\Ui\Admin\Path::getModuleUrl('trading_edit', [ 'lang' => LANGUAGE_ID ])
			. '&id=#ID#';

		return $field;
	}

	protected static function extendTraceDescription($field)
	{
		$field['USER_TYPE'] = Market\Ui\UserField\Manager::getUserType('trace');

		return $field;
	}

	protected static function extendContextDescription($field)
	{
		$field['USER_TYPE'] = Market\Ui\UserField\Manager::getUserType('logMessage');

		return $field;
	}
}