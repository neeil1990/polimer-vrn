<?php
namespace Yandex\Market\Watcher\Track;

use Bitrix\Main;
use Yandex\Market;

class BindTable extends Market\Reference\Storage\Table
{
	public static function getTableName()
	{
		return 'yamarket_watcher_bind';
	}

	public static function createIndexes(Main\DB\Connection $connection)
	{
		$tableName = static::getTableName();

		$connection->createIndex($tableName, 'IX_' . $tableName . '_0', [ 'SERVICE', 'SETUP_ID' ]);
		$connection->createIndex($tableName, 'IX_' . $tableName . '_1', [ 'OWNER_TYPE', 'OWNER_ID' ]);
		$connection->createIndex($tableName, 'IX_' . $tableName . '_2', [ 'ELEMENT_TYPE', 'ELEMENT_GROUP' ]);
	}

	public static function getMap()
	{
		return [
			new Main\Entity\IntegerField('ID', [
				'primary' => true,
				'autocomplete' => true,
			]),
			new Main\Entity\EnumField('SERVICE', [
				'required' => true,
				'values' => [
					Market\Glossary::SERVICE_EXPORT,
					Market\Glossary::SERVICE_SALES_BOOST,
				],
			]),
			new Main\Entity\IntegerField('SETUP_ID', [
				'required' => true,
			]),
			new Main\Entity\EnumField('OWNER_TYPE', [
				'required' => true,
				'values' => [
					Market\Glossary::ENTITY_SETUP,
					Market\Export\Glossary::ENTITY_PROMO,
					Market\Export\Glossary::ENTITY_COLLECTION,
				]
			]),
			new Main\Entity\IntegerField('OWNER_ID', [
				'required' => true
			]),
			new Main\Entity\StringField('ELEMENT_TYPE', [
				'required' => true,
				'validation' => function() {
					return [
						new Main\Entity\Validator\Length(null, 20),
					];
				},
			]),
			new Main\Entity\StringField('ELEMENT_GROUP', [
				'required' => true,
				'default_value' => 0,
				'validation' => function() {
					return [
						new Main\Entity\Validator\Length(null, 5),
					];
				},
			]),
			new Main\Entity\StringField('REPLACE_TYPE', [
				'validation' => function() {
					return [
						new Main\Entity\Validator\Length(null, 20),
					];
				},
			]),
		];
	}
}