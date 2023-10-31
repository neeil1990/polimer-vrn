<?php
namespace Yandex\Market\SalesBoost\Run\Storage;

use Bitrix\Main;
use Yandex\Market\Reference;
use Yandex\Market\SalesBoost;

class CollectorTable extends Reference\Storage\Table
{
	const STATUS_ACTIVE = 'A';
	const STATUS_INACTIVE = 'I';
	const STATUS_FAIL = 'F';
	const STATUS_DELETE = 'D';

	public static function getTableName()
	{
		return 'yamarket_sales_boost_collector';
	}

	public static function createIndexes(Main\DB\Connection $connection)
	{
		$tableName = static::getTableName();

		$connection->createIndex($tableName, 'IX_' . $tableName . '_1', [ 'BUSINESS_ID', 'SKU' ]);
		$connection->createIndex($tableName, 'IX_' . $tableName . '_2', [ 'TIMESTAMP_X' ]);
		$connection->createIndex($tableName, 'IX_' . $tableName . '_3', [ 'STATUS' ]);
		$connection->createIndex($tableName, 'IX_' . $tableName . '_4', [ 'PARENT_ID' ]);
	}

	public static function getMap()
	{
		return [
			new Main\Entity\IntegerField('BOOST_ID', [
				'primary' => true,
			]),
			new Main\Entity\IntegerField('ELEMENT_ID', [
				'primary' => true,
			]),
			new Main\Entity\IntegerField('PARENT_ID'),
			new Main\Entity\IntegerField('BUSINESS_ID'),
			new Main\Entity\StringField('SKU', [
				'validation' => function() {
					return [ new Main\Entity\Validator\Length(null, 80) ];
				},
			]),
			new Main\Entity\IntegerField('SORT'),
			new Main\Entity\EnumField('STATUS', [
				'required' => true,
				'values' => [
					static::STATUS_ACTIVE,
					static::STATUS_INACTIVE,
					static::STATUS_FAIL,
					static::STATUS_DELETE,
				],
			]),
			new Main\Entity\IntegerField('BID'),
			new Reference\Storage\Field\CanonicalDateTime('TIMESTAMP_X', [
				'required' => true,
			]),

			new Main\Entity\ReferenceField('SUBMITTER', SubmitterTable::class, [
				'=this.BOOST_ID' => 'ref.BOOST_ID',
				'=this.ELEMENT_ID' => 'ref.ELEMENT_ID',
			]),

			new Main\Entity\ReferenceField('BOOST', SalesBoost\Setup\Table::class, [
				'=this.BOOST_ID' => 'ref.ID',
			]),
		];
	}

}