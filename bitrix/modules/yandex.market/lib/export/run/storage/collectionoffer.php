<?php

namespace Yandex\Market\Export\Run\Storage;

use Bitrix\Main;
use Yandex\Market;

class CollectionOfferTable extends Market\Reference\Storage\Table
{
	public static function getTableName()
	{
		return 'yamarket_export_run_collection_offer';
	}

	public static function createIndexes(Main\DB\Connection $connection)
	{
		$tableName = static::getTableName();

		$connection->createIndex($tableName, 'IX_' . $tableName . '_1', [ 'STATUS', 'WRITTEN' ]);
		$connection->createIndex($tableName, 'IX_' . $tableName . '_2', [ 'TIMESTAMP_X' ]);
		$connection->createIndex($tableName, 'IX_' . $tableName . '_3', [ 'COLLECTION_ID', 'COLLECTION_SIGN' ]);
		$connection->createIndex($tableName, 'IX_' . $tableName . '_4', [ 'PRIORITY' ]);
	}

	public static function getMap()
	{
		return [
			new Main\Entity\IntegerField('SETUP_ID', [
				'required' => true,
				'primary' => true,
			]),
			new Main\Entity\IntegerField('ELEMENT_ID', [ // OFFER_ID
				'required' => true,
				'primary' => true,
			]),
			new Main\Entity\StringField('COLLECTION_SIGN', [ // STRATEGY_PRIMARY . '' . COLLECTION_ID
				'required' => true,
				'primary' => true,
				'validation' => function() {
					return [
						new Main\Entity\Validator\Length(null, 20),
					];
				},
			]),
			new Main\Entity\IntegerField('COLLECTION_ID', [
				'required' => true,
			]),
			new Main\Entity\IntegerField('PRIORITY', [
				'required' => true,
			]),
			new Main\Entity\StringField('STATUS', [
				'required' => true,
				'size' => 1,
				'validation' => function() {
					return [
						new Main\Entity\Validator\Length(null, 1),
					];
				},
			]),
			new Main\Entity\BooleanField('WRITTEN', [
				'required' => true,
				'values' => [ static::BOOLEAN_N, static::BOOLEAN_Y ],
			]),
			new Market\Reference\Storage\Field\CanonicalDateTime('TIMESTAMP_X', [
				'required' => true
			]),

			new Main\Entity\ReferenceField('OFFER', OfferTable::class, [
				'=this.SETUP_ID' => 'ref.SETUP_ID',
				'=this.ELEMENT_ID' => 'ref.ELEMENT_ID',
			]),

			new Main\Entity\ReferenceField('COLLECTION', CollectionTable::class, [
				'=this.SETUP_ID' => 'ref.SETUP_ID',
				'=this.COLLECTION_SIGN' => 'ref.ELEMENT_ID',
				'=this.COLLECTION_ID' => 'ref.COLLECTION_ID',
			]),
		];
	}
}