<?php

namespace Yandex\Market\Export\Run\Storage;

use Bitrix\Main;
use Yandex\Market;

class AgentTable extends Market\Reference\Storage\Table
{
	public static function getTableName()
	{
		return 'yamarket_export_run_agent';
	}

	public static function getMap()
	{
		return [
			new Main\Entity\StringField('METHOD', [
				'required' => true,
				'primary' => true,
				'size' => 15,
				'validation' => [__CLASS__, 'validateMethod']
			]),
			new Main\Entity\IntegerField('SETUP_ID', [
				'primary' => true,
				'required' => true
			]),
			new Main\Entity\StringField('STEP', [
				'size' => 15,
				'validation' => [__CLASS__, 'validateStep']
			]),
			new Main\Entity\StringField('OFFSET', [
				'size' => 64,
				'validation' => [__CLASS__, 'validateOffset']
			]),
			new Main\Entity\DatetimeField('START_TIME')
		];
	}

	public static function validateMethod()
	{
		return [
			new Main\Entity\Validator\Length(null, 15)
		];
	}

	public static function validateStep()
	{
		return [
			new Main\Entity\Validator\Length(null, 15)
		];
	}

	public static function validateOffset()
	{
		return [
			new Main\Entity\Validator\Length(null, 64)
		];
	}

	public static function migrate(Main\DB\Connection $connection)
	{
		static::migrateIncreaseOffsetLength($connection);
	}

	protected static function migrateIncreaseOffsetLength(Main\DB\Connection $connection)
	{
		$sqlHelper = $connection->getSqlHelper();
		$tableName = static::getTableName();
		$columnName = 'OFFSET';

		$queryColumns = $connection->query(sprintf('SHOW COLUMNS FROM %s LIKE "%s"',
			$sqlHelper->quote($tableName),
			$sqlHelper->forSql($columnName)
		));
		$column = $queryColumns->fetch();

		if (isset($column['Type']) && Market\Data\TextString::getPosition($column['Type'], '20') !== false)
		{
			$entity = static::getEntity();
			$field = $entity->getField($columnName);

			if (!($field instanceof Main\Entity\ScalarField))
			{
				throw new Main\SystemException('OFFSET must be scalar');
			}

			$columnType = $sqlHelper->getColumnTypeByField($field);

			$connection->queryExecute(sprintf(
				'ALTER TABLE %s MODIFY COLUMN %s %s',
				$sqlHelper->quote($tableName),
				$sqlHelper->quote($columnName),
				$columnType
			));
		}
	}
}