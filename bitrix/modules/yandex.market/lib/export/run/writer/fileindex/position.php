<?php

namespace Yandex\Market\Export\Run\Writer\FileIndex;

use Bitrix\Main;
use Yandex\Market\Reference\Storage;

class PositionTable extends Storage\Table
{
	public static function createIndexes(Main\DB\Connection $connection)
	{
		$tableName = static::getTableName();

		$connection->createIndex($tableName, 'IX_' . $tableName . '_1', [ 'POSITION' ]);
	}

	public static function getTableName()
	{
		return 'yamarket_export_file_position';
	}

	public static function getMap()
	{
		return [
			new Main\Entity\IntegerField('SETUP_ID', [
				'primary' => true,
			]),
			new Main\Entity\StringField('NAME', [
				'primary' => true,
				'validation' => function() {
					return [
						new Main\Entity\Validator\Length(null, 22),
					];
				},
			]),
			new Main\Entity\StringField('PRIMARY', [
				'primary' => true,
				'validation' => function() {
					return [
						new Main\Entity\Validator\Length(null, 80),
					];
				},
			]),
			new Main\Entity\IntegerField('POSITION', [
				'required' => true,
			]),
			new Main\Entity\IntegerField('SIZE', [
				'required' => true,
			]),
		];
	}
}