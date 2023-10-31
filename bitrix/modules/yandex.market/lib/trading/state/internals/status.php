<?php

namespace Yandex\Market\Trading\State\Internals;

use Yandex\Market;
use Bitrix\Main;

class StatusTable extends Market\Reference\Storage\Table
{
	public static function getTableName()
	{
		return 'yamarket_trading_state_status';
	}

	public static function getMap()
	{
		return [
			new Main\Entity\StringField('SERVICE', [
				'required' => true,
				'primary' => true,
				'validation' => [__CLASS__, 'getValidationForService'],
			]),
			new Main\Entity\StringField('ENTITY_ID', [
				'required' => true,
				'primary' => true,
				'validation' => [__CLASS__, 'getValidationForEntityId'],
			]),
			new Main\Entity\StringField('VALUE', [
				'required' => true,
				'validation' => [__CLASS__, 'getValidationForValue'],
			]),
			new Main\Entity\DatetimeField('TIMESTAMP_X', [
				'required' => true,
			]),
		];
	}

	public static function migrate(Main\DB\Connection $connection)
	{
		$tableName = static::getTableName();
		$existsFields = $connection->getTableFields($tableName);

		static::migrateCommon($connection);
		static::migrateTimestampX($connection, $existsFields);
	}

	protected static function migrateCommon(Main\DB\Connection $connection)
	{
		$entity = static::getEntity();

		Market\Migration\StorageFacade::addNewFields($connection, $entity);
		Market\Migration\StorageFacade::updateFieldsLength($connection, $entity, [ 'VALUE' ]);
	}

	protected static function migrateTimestampX(Main\DB\Connection $connection, array $existsFields)
	{
		$entity = static::getEntity();
		$tableName = static::getTableName();

		if (!isset($existsFields['TIMESTAMP_X']))
		{
			$sqlHelper = $connection->getSqlHelper();

			$connection->queryExecute(sprintf(
				'UPDATE %s SET %s=%s',
				$sqlHelper->quote($tableName),
				$sqlHelper->quote('TIMESTAMP_X'),
				$sqlHelper->convertToDb(static::emulatedTimestampX(), $entity->getField('TIMESTAMP_X'))
			));
		}
		else
		{
			$minimalTimestamp = static::minimalTimestampX();
			$emulatedTimestamp = static::emulatedTimestampX();

			if ($minimalTimestamp === null) { return; }
			if (Market\Data\DateTime::compare($minimalTimestamp, $emulatedTimestamp) !== 1) { return; }
			if (!static::hasFewWithSameTimestampX($minimalTimestamp)) { return; }

			static::updateBatch([
				'filter' => [ '=TIMESTAMP_X' => $minimalTimestamp ],
			], [
				'TIMESTAMP_X' => $emulatedTimestamp,
			]);
		}
	}

	protected static function emulatedTimestampX()
	{
		$days = (int)Market\Config::getOption('trading_reserve_days', 7);
		$days = max(1, $days);
		++$days;

		$result = new Main\Type\DateTime();
		$result->add(sprintf('-P%sD', $days));
		$result->setTime(0, 0);

		return $result;
	}

	protected static function minimalTimestampX()
	{
		$result = null;

		$query = static::getList([
			'select' => [ 'TIMESTAMP_X' ],
			'order' => [ 'TIMESTAMP_X' => 'asc' ],
			'limit' => 1,
		]);

		if ($row = $query->fetch())
		{
			$result = $row['TIMESTAMP_X'];
		}

		return $result;
	}

	protected static function hasFewWithSameTimestampX(Main\Type\DateTime $timestamp)
	{
		$query = static::getList([
			'filter' => [ '=TIMESTAMP_X' => $timestamp ],
			'select' => [ 'ENTITY_ID' ],
			'limit' => 2,
		]);

		$rows = $query->fetchAll();

		return count($rows) > 1;
	}

	public static function getValidationForService()
	{
		return [
			new Main\Entity\Validator\Length(null, 20),
		];
	}

	public static function getValidationForEntityId()
	{
		return [
			new Main\Entity\Validator\Length(null, 12),
		];
	}

	public static function getValidationForValue()
	{
		return [
			new Main\Entity\Validator\Length(null, 60),
		];
	}
}