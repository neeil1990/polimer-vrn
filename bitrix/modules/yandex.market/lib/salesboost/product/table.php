<?php

namespace Yandex\Market\SalesBoost\Product;

use Yandex\Market\Reference;
use Yandex\Market\SalesBoost;
use Yandex\Market\Export;
use Bitrix\Main;

class Table extends Reference\Storage\Table
{
	public static function getTableName()
	{
		return 'yamarket_sales_boost_product';
	}

	public static function getMap()
	{
		return [
			new Main\Entity\IntegerField('ID', [
				'autocomplete' => true,
				'primary' => true,
			]),

			new Main\Entity\IntegerField('SETUP_ID'),
			new Main\Entity\ReferenceField('SETUP', SalesBoost\Setup\Table::class, [
				'=this.SETUP_ID' => 'ref.ID',
			]),

			new Main\Entity\IntegerField('IBLOCK_ID'),
			new Main\Entity\ReferenceField('IBLOCK', 'Bitrix\Iblock\Iblock', [
				'=this.IBLOCK_ID' => 'ref.ID',
			]),

			new Main\Entity\ReferenceField('FILTER', Export\Filter\Table::class, [
				'=ref.ENTITY_TYPE' => [ '?', Export\Filter\Table::ENTITY_TYPE_SALES_BOOST ],
				'=ref.ENTITY_ID' => 'this.ID',
			]),
		];
	}

	public static function getReference($primary = null)
	{
		return [
			'FILTER' => [
				'TABLE' => Export\Filter\Table::class,
				'LINK_FIELD' => 'ENTITY_ID',
				'LINK' => [
					'ENTITY_TYPE' => Export\Filter\Table::ENTITY_TYPE_SALES_BOOST,
					'ENTITY_ID' => $primary,
				],
			]
		];
	}
}