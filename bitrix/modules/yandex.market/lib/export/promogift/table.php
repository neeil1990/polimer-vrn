<?php

namespace Yandex\Market\Export\PromoGift;

use Yandex\Market;
use Bitrix\Main;

class Table extends Market\Reference\Storage\Table
{
    const PROMO_GIFT_TYPE_OFFER = 1;
    const PROMO_GIFT_TYPE_GIFT = 2;

	public static function getTableName()
	{
		return 'yamarket_export_promo_gift';
	}

	public static function getUfId()
	{
		return 'YAMARKET_EXPORT_PROMO_GIFT';
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

            new Main\Entity\BooleanField('EXPORT_GIFT', [
                'values' => [ static::BOOLEAN_N, static::BOOLEAN_Y ],
                'default_value' => static::BOOLEAN_Y
            ]),

			new Main\Entity\ReferenceField('FILTER', Market\Export\Filter\Table::getClassName(), [
				'=ref.ENTITY_TYPE' => [ '?', Market\Export\Filter\Table::ENTITY_TYPE_PROMO_GIFT ],
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
					'ENTITY_TYPE' => Market\Export\Filter\Table::ENTITY_TYPE_PROMO_GIFT,
					'ENTITY_ID' => $primary
				]
			]
		];
	}

	public static function migrate(Main\DB\Connection $connection)
	{
		$sqlHelper = $connection->getSqlHelper();
		$promoGiftTableName = static::getTableName();
		$promoGiftTableFields = $connection->getTableFields($promoGiftTableName);

		if (!isset($promoGiftTableFields['EXPORT_GIFT']))
		{
			$connection->queryExecute(
				'ALTER TABLE ' . $sqlHelper->quote($promoGiftTableName)
				. ' ADD COLUMN ' . $sqlHelper->quote('EXPORT_GIFT') . ' int NOT NULL'
			);
		}
	}
}