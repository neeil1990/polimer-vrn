<?php
namespace Yandex\Market\Watcher\Agent;

use Bitrix\Main;
use Yandex\Market;

class StateTable extends Market\Reference\Storage\Table
{
	const VERSION = 2;

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
				'validation' => function() {
					return [
						new Main\Entity\Validator\Length(null, 15)
					];
				},
			]),
			new Main\Entity\EnumField('SETUP_TYPE', [
				'primary' => true,
				'required' => true,
				'values' => [
					Market\Glossary::SERVICE_EXPORT,
					Market\Glossary::SERVICE_SALES_BOOST,
				],
			]),
			new Main\Entity\IntegerField('SETUP_ID', [
				'primary' => true,
				'required' => true
			]),
			new Main\Entity\StringField('STEP', [
				'validation' => function() {
					return [
						new Main\Entity\Validator\Length(null, 15)
					];
				},
			]),
			new Main\Entity\StringField('OFFSET', [
				'validation' => function() {
					return [
						new Main\Entity\Validator\Length(null, 64)
					];
				},
			]),
			new Market\Reference\Storage\Field\CanonicalDateTime('START_TIME'),
			new Main\Entity\EnumField('VERSION', [
				'required' => true,
				'default_value' => static::VERSION,
				'values' => range(1, static::VERSION),
			]),
		];
	}

	public static function migrate(Main\DB\Connection $connection)
	{
		Market\Migration\StorageFacade::updateFieldsLength($connection, static::getEntity(), [ 'OFFSET' ]);
		static::migrateSetupType($connection);
	}

	/** @noinspection DuplicatedCode */
	protected static function migrateSetupType(Main\DB\Connection $connection)
	{
		$tableName = static::getTableName();
		$knownFields = $connection->getTableFields($tableName);

		if (isset($knownFields['SETUP_TYPE'])) { return; }

		$sqlHelper = $connection->getSqlHelper();

		Market\Migration\StorageFacade::addNewFields($connection, static::getEntity());
		Market\Migration\StorageFacade::dropPrimary($connection, static::getEntity());

		$connection->queryExecute(sprintf(
			'UPDATE %s SET %s="%s"',
			$sqlHelper->quote($tableName),
			$sqlHelper->quote('SETUP_TYPE'),
			$sqlHelper->forSql(Market\Glossary::SERVICE_EXPORT)
		));

		Market\Migration\StorageFacade::createPrimary($connection, static::getEntity());
	}
}