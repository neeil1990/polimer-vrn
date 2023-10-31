<?php
namespace Yandex\Market\SalesBoost\Run\Storage;

use Bitrix\Main;
use Yandex\Market\Reference;
use Yandex\Market\SalesBoost;

class SubmitterTable extends Reference\Storage\Table
{
	const STATUS_READY = 'R';
	const STATUS_ACTIVE = 'A';
	const STATUS_ERROR = 'E';
	const STATUS_DELETE = 'D';

	public static function getTableName()
	{
		return 'yamarket_sales_boost_submitter';
	}

	public static function createIndexes(Main\DB\Connection $connection)
	{
		$tableName = static::getTableName();

		$connection->createIndex($tableName, 'IX_' . $tableName . '_1', [ 'BOOST_ID', 'ELEMENT_ID' ]);
		$connection->createIndex($tableName, 'IX_' . $tableName . '_2', [ 'TIMESTAMP_X' ]);
		$connection->createIndex($tableName, 'IX_' . $tableName . '_3', [ 'STATUS' ]);
	}

	public static function getMap()
	{
		return [
			new Main\Entity\IntegerField('BUSINESS_ID', [
				'primary' => true,
			]),
			new Main\Entity\StringField('SKU', [
				'primary' => true,
				'validation' => function() {
					return [ new Main\Entity\Validator\Length(null, 80) ];
				},
			]),
			new Main\Entity\IntegerField('BOOST_ID', [
				'required' => true,
			]),
			new Main\Entity\IntegerField('ELEMENT_ID', [
				'required' => true,
			]),
			new Main\Entity\EnumField('STATUS', [
				'required' => true,
				'values' => [
					static::STATUS_READY,
					static::STATUS_ACTIVE,
					static::STATUS_ERROR,
					static::STATUS_DELETE,
				],
			]),
			new Main\Entity\IntegerField('BID', [
				'required' => true,
			]),
			new Reference\Storage\Field\CanonicalDateTime('TIMESTAMP_X', [
				'required' => true,
			]),

			new Main\Entity\ReferenceField('COLLECTOR', CollectorTable::class, [
				'=this.BOOST_ID' => 'ref.BOOST_ID',
				'=this.ELEMENT_ID' => 'ref.ELEMENT_ID',
			]),

			new Main\Entity\ReferenceField('BOOST', SalesBoost\Setup\Table::class, [
				'=this.BOOST_ID' => 'ref.ID',
			]),
		];
	}

}