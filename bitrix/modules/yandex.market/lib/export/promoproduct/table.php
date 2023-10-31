<?php

namespace Yandex\Market\Export\PromoProduct;

use Yandex\Market;
use Bitrix\Main;

class Table extends Market\Reference\Storage\Table
{
    const PROMO_PRODUCT_TYPE_OFFER = 1;
    const PROMO_PRODUCT_TYPE_OFFER_WITH_DISCOUNT = 2;
    const PROMO_PRODUCT_TYPE_CATEGORY = 3;

	public static function getTableName()
	{
		return 'yamarket_export_promo_product';
	}

	public static function getUfId()
	{
		return 'YAMARKET_EXPORT_PROMO_PRODUCT';
	}

	public static function getMap()
	{
		return [
			new Main\Entity\IntegerField('ID', [
				'autocomplete' => true,
				'primary' => true
			]),

			new Main\Entity\IntegerField('PROMO_ID'),
			new Main\Entity\ReferenceField('PROMO', Market\Export\Promo\Table::getClassName(), [
				'=this.PROMO_ID' => 'ref.ID'
			]),

			new Main\Entity\IntegerField('IBLOCK_ID'),
			new Main\Entity\ReferenceField('IBLOCK', 'Bitrix\Iblock\Iblock', [
				'=this.IBLOCK_ID' => 'ref.ID'
			]),

			new Main\Entity\ReferenceField('FILTER', Market\Export\Filter\Table::getClassName(), [
				'=ref.ENTITY_TYPE' => [ '?', Market\Export\Filter\Table::ENTITY_TYPE_PROMO_PRODUCT ],
				'=ref.ENTITY_ID' => 'this.ID'
			]),
		];
	}

	public static function getReference($primary = null)
	{
		return [
			'FILTER' => [
				'TABLE' => Market\Export\Filter\Table::getClassName(),
				'LINK_FIELD' => 'ENTITY_ID',
				'LINK' => [
					'ENTITY_TYPE' => Market\Export\Filter\Table::ENTITY_TYPE_PROMO_PRODUCT,
					'ENTITY_ID' => $primary
				]
			]
		];
	}
}