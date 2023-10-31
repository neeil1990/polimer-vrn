<?php
namespace Yandex\Market\Export\CollectionProduct;

use Yandex\Market;
use Bitrix\Main;

class Table extends Market\Reference\Storage\Table
{
	public static function getTableName()
	{
		return 'yamarket_export_collection_product';
	}

	public static function getMap()
	{
		return [
			new Main\Entity\IntegerField('ID', [
				'autocomplete' => true,
				'primary' => true
			]),

			new Main\Entity\IntegerField('COLLECTION_ID'),
			new Main\Entity\ReferenceField('COLLECTION', Market\Export\Collection\Table::class, [
				'=this.COLLECTION_ID' => 'ref.ID'
			]),

			new Main\Entity\IntegerField('IBLOCK_ID'),

			new Main\Entity\ReferenceField('FILTER', Market\Export\Filter\Table::class, [
				'=ref.ENTITY_TYPE' => [ '?', Market\Export\Filter\Table::ENTITY_TYPE_COLLECTION_PRODUCT ],
				'=ref.ENTITY_ID' => 'this.ID',
			]),
		];
	}

	public static function getReference($primary = null)
	{
		return [
			'FILTER' => [
				'TABLE' => Market\Export\Filter\Table::class,
				'LINK_FIELD' => 'ENTITY_ID',
				'LINK' => [
					'ENTITY_TYPE' => Market\Export\Filter\Table::ENTITY_TYPE_COLLECTION_PRODUCT,
					'ENTITY_ID' => $primary,
				],
			],
		];
	}
}