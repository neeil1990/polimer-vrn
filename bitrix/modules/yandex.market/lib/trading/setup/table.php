<?php

namespace Yandex\Market\Trading\Setup;

use Bitrix\Main;
use Yandex\Market;

Main\Localization\Loc::loadMessages(__FILE__);

class Table extends Market\Reference\Storage\Table
{
	public static function getTableName()
	{
		return 'yamarket_trading_setup';
	}

	public static function getUfId()
	{
		return 'YAMARKET_TRADING_SETUP';
	}

	public static function getMap()
	{
		return [
			new Main\Entity\IntegerField('ID', [
				'autocomplete' => true,
				'primary' => true
			]),
			new Main\Entity\StringField('NAME', [
				'required' => true,
				'validation' => [__CLASS__, 'validateName'],
			]),
			new Main\Entity\BooleanField('ACTIVE', [
				'values' => [static::BOOLEAN_N, static::BOOLEAN_Y],
				'default_value' => static::BOOLEAN_Y,
			]),
			new Main\Entity\StringField('TRADING_SERVICE', [
				'required' => true,
				'validation' => [__CLASS__, 'validateTradingService'],
			]),
			new Main\Entity\StringField('TRADING_BEHAVIOR', [
				'required' => true,
				'validation' => [__CLASS__, 'validateTradingBehavior'],
				'default_value' => Market\Trading\Service\Manager::BEHAVIOR_DEFAULT,
			]),
			new Main\Entity\StringField('CODE', [
				'required' => true,
				'validation' => [__CLASS__, 'validateCode'],
			]),
			new Main\Entity\StringField('SITE_ID', [
				'required' => true,
				'validation' => [__CLASS__, 'validateSiteId'],
			]),
			new Main\Entity\StringField('EXTERNAL_ID', [
				'required' => true,
				'validation' => [__CLASS__, 'validateExternalId'],
			]),

			new Main\Entity\ReferenceField('SETTINGS', Market\Trading\Settings\Table::class, [
				'=this.ID' => 'ref.SETUP_ID'
			]),
			new Main\Entity\ReferenceField('BUSINESS', Market\Trading\Business\Table::class, [
				'=this.SETTINGS.NAME' => [ '?', 'BUSINESS_ID' ],
				'=ref.ID' => 'this.SETTINGS.VALUE',
			]),
		];
	}

	public static function onAfterUpdate(Main\Entity\Event $event)
	{
		$cache = Main\Application::getInstance()->getManagedCache();
		$tableName = static::getTableName();

		$cache->cleanDir($tableName);
	}

	public static function migrate(Main\DB\Connection $connection)
	{
		parent::migrate($connection);
		static::migrateIncreaseServiceLength($connection);
		static::migrateFillDefaultBehavior($connection);
		static::migrateCode($connection);
	}

	protected static function migrateIncreaseServiceLength(Main\DB\Connection $connection)
	{
		$entity = static::getEntity();

		Market\Migration\StorageFacade::updateFieldsLength($connection, $entity, [ 'TRADING_SERVICE' ]);
	}

	protected static function migrateFillDefaultBehavior(Main\DB\Connection $connection)
	{
		$sqlHelper = $connection->getSqlHelper();
		$tableName = static::getTableName();

		$connection->queryExecute(sprintf(
			'UPDATE %1$s SET %2$s=\'%3$s\' WHERE %2$s is null or %2$s=\'\'',
			$sqlHelper->quote($tableName),
			$sqlHelper->quote('TRADING_BEHAVIOR'),
			$sqlHelper->forSql(Market\Trading\Service\Manager::BEHAVIOR_DEFAULT)
		));
	}

	protected static function migrateCode(Main\DB\Connection $connection)
	{
		$sqlHelper = $connection->getSqlHelper();
		$tableName = static::getTableName();

		$connection->queryExecute(sprintf(
			'UPDATE %1$s SET %2$s=%3$s WHERE %2$s is null or %2$s=\'\'',
			$sqlHelper->quote($tableName),
			$sqlHelper->quote('CODE'),
			$sqlHelper->quote('SITE_ID')
		));
	}

	public static function getReference($primary = null)
	{
		return [
			'SETTINGS' => [
				'TABLE' => Market\Trading\Settings\Table::getClassName(),
				'LINK_FIELD' => 'SETUP_ID',
				'LINK' => [
					'SETUP_ID' => $primary,
				],
			],
		];
	}

	public static function validateName()
	{
		return [
			new Main\Entity\Validator\Length(null, 65),
		];
	}

	public static function validateTradingService()
	{
		return [
			new Main\Entity\Validator\Length(null, 20),
		];
	}

	public static function validateTradingBehavior()
	{
		return [
			new Main\Entity\Validator\Length(null, 20),
		];
	}

	public static function validateSiteId()
	{
		return [
			new Main\Entity\Validator\Length(null, 10),
		];
	}

	public static function isReservedCode($code)
	{
		$reserved = [
			'cart' => true,
			'order' => true,
			'stocks' => true,
		];

		return isset($reserved[$code]);
	}

	public static function validateCode()
	{
		return [
			new Main\Entity\Validator\Length(null, 10),
			static function ($value, $primary, $row, $field)
			{
				$value = trim($value);

				if (!preg_match('/^[a-z0-9_-]+$/i', $value))
				{
					return Market\Config::getLang('EXPORT_TRADING_SETUP_CODE_INVALID_CHARS');
				}

				if (static::isReservedCode($value))
				{
					return Market\Config::getLang('EXPORT_TRADING_SETUP_CODE_MATCH_RESERVED');
				}

				if (static::testCodeChanged($value, $primary, $row) && !static::testCodeUnique($value, $primary, $row))
				{
					return Market\Config::getLang('EXPORT_TRADING_SETUP_CODE_NOT_UNIQUE');
				}

				return true;
			}
		];
	}

	public static function validateExternalId()
	{
		return [
			new Main\Entity\Validator\Length(null, 20),
		];
	}

	protected static function testCodeChanged($code, $primary, $row)
	{
		if ($primary === null) { return true; }

		$result = true;
		$primaryId = is_scalar($primary) ? $primary : $primary['ID'];

		$query = static::getList([
			'filter' => [ '=ID' => $primaryId ],
			'select' => [ 'CODE' ],
		]);

		if ($exists = $query->fetch())
		{
			$result = ((string)$exists['CODE'] !== (string)$code);
		}

		return $result;
	}

	protected static function testCodeUnique($code, $primary, $row)
	{
		$row = static::fulfilExistsData($primary, $row, [
			'TRADING_SERVICE',
			'TRADING_BEHAVIOR',
		]);

		$filter = [
			'=TRADING_SERVICE' => $row['TRADING_SERVICE'],
			'=TRADING_BEHAVIOR' => $row['TRADING_BEHAVIOR'],
			'=CODE' => $code,
		];

		if ($primary !== null)
		{
			$primaryId = is_scalar($primary) ? $primary : $primary['ID'];

			$filter['!=ID'] = $primaryId;
		}

		$row = static::getList([ 'filter' => $filter, 'limit' => 1 ])->fetch();

		return ($row === false);
	}

	protected static function fulfilExistsData($primary, $row, $select)
	{
		$needSelect = array_diff_key(array_flip($select), $row);

		if (empty($needSelect)) { return $row; }

		$primaryId = is_scalar($primary) ? $primary : $primary['ID'];

		$query = static::getList([
			'filter' => [ '=ID' => $primaryId ],
			'select' => array_keys($needSelect),
		]);

		if ($exists = $query->fetch())
		{
			$row += $exists;
		}

		return $row;
	}
}
