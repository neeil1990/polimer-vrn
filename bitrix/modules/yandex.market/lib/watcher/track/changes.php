<?php
namespace Yandex\Market\Watcher\Track;

use Bitrix\Main;
use Yandex\Market;

class ChangesTable extends Market\Reference\Storage\Table
{
	public static function getTableName()
	{
		return 'yamarket_watcher_changes';
	}

	public static function createIndexes(Main\DB\Connection $connection)
	{
		$tableName = static::getTableName();

		$connection->createIndex($tableName, 'IX_' . $tableName . '_0', [ 'ELEMENT_TYPE', 'ELEMENT_GROUP' ]);
	}

	public static function getMap()
	{
		return [
			new Main\Entity\IntegerField('ID', [
				'primary' => true,
				'autocomplete' => true,
			]),
			new Main\Entity\StringField('ELEMENT_TYPE', [
				'required' => true,
				'validation' => function() {
					return [
						new Main\Entity\Validator\Length(null, 20),
					];
				},
			]),
			new Main\Entity\StringField('ELEMENT_ID', [
				'required' => true,
				'validation' => function() {
					return [
						new Main\Entity\Validator\Length(null, 20),
					];
				},
			]), // may be currency id and bigInt
			new Main\Entity\StringField('ELEMENT_GROUP', [
				'required' => true,
				'default_value' => 0,
				'validation' => function() {
					return [
						new Main\Entity\Validator\Length(null, 5),
					];
				},
			]),

			new Main\Entity\ReferenceField('BIND', BindTable::class, [
				'=this.ELEMENT_TYPE' => 'ref.ELEMENT_TYPE',
				'=this.ELEMENT_GROUP' => 'ref.ELEMENT_GROUP',
			])
		];
	}
}