<?php

namespace Yandex\Market\Export\Run\Storage;

use Bitrix\Main;
use Yandex\Market;

class PromoGiftTable extends Market\Reference\Storage\Table
{
    public static function getTableName()
    {
        return 'yamarket_export_run_promo_gift';
    }

    public static function createIndexes(Main\DB\Connection $connection)
    {
        $tableName = static::getTableName();

        $connection->createIndex($tableName, 'IX_' . $tableName . '_1', [ 'STATUS' ]);
        $connection->createIndex($tableName, 'IX_' . $tableName . '_2', [ 'TIMESTAMP_X' ]);
        $connection->createIndex($tableName, 'IX_' . $tableName . '_3', [ 'ELEMENT_TYPE' ]);
        $connection->createIndex($tableName, 'IX_' . $tableName . '_4', [ 'PRIMARY' ]);
    }

    public static function getMap()
    {
        return [
            new Main\Entity\IntegerField('SETUP_ID', [
                'required' => true,
                'primary' => true
            ]),
            new Main\Entity\IntegerField('PROMO_ID', [
                'required' => true,
                'primary' => true
            ]),
            new Main\Entity\IntegerField('ELEMENT_ID', [
                'required' => true,
                'primary' => true
            ]),
            new Main\Entity\EnumField('ELEMENT_TYPE', [
                'required' => true,
                'default_value' => Market\Export\PromoGift\Table::PROMO_GIFT_TYPE_OFFER,
                'values' => [
                    Market\Export\PromoGift\Table::PROMO_GIFT_TYPE_OFFER,
                    Market\Export\PromoGift\Table::PROMO_GIFT_TYPE_GIFT
                ]
            ]),
	        new Main\Entity\StringField('PRIMARY', [
		        'size' => 30,
		        'validation' => [__CLASS__, 'getValidationForPrimary'],
	        ]),
            new Main\Entity\StringField('HASH', [
                'size' => 33, // md5
                'validation' => [__CLASS__, 'validateHash'],
            ]),
            new Main\Entity\StringField('STATUS', [
                'size' => 1,
                'validation' => [__CLASS__, 'validateStatus'],
            ]),
            new Main\Entity\TextField('CONTENTS'),
            new Main\Entity\DatetimeField('TIMESTAMP_X', [
                'required' => true
            ]),

	        // additional fields

	        new Main\Entity\IntegerField('PARENT_ID'),
        ];
    }

	public static function migrate(Main\DB\Connection $connection)
	{
		$sqlHelper = $connection->getSqlHelper();
		$tableName = static::getTableName();
		$tableFields = $connection->getTableFields($tableName);

		if (!isset($tableFields['PRIMARY']))
		{
			// add column

			$connection->queryExecute(sprintf(
				'ALTER TABLE %s ADD COLUMN %s varchar(30) NOT NULL',
				$sqlHelper->quote($tableName),
				$sqlHelper->quote('PRIMARY')
			));

			// fill primary for success exported elements

			$connection->queryExecute(sprintf(
				'UPDATE %s SET %s=%s WHERE %s=%s',
				$sqlHelper->quote($tableName),
				$sqlHelper->quote('PRIMARY'),
				$sqlHelper->quote('ELEMENT_ID'),
				$sqlHelper->quote('STATUS'),
				$sqlHelper->forSql(Market\Export\Run\Steps\Base::STORAGE_STATUS_SUCCESS)
			));

			// primary index

			$connection->createIndex($tableName, 'IX_' . $tableName . '_4', [ 'PRIMARY' ]);
		}

		if (!isset($tableFields['PARENT_ID']))
		{
			$connection->queryExecute(sprintf(
				'ALTER TABLE %s ADD COLUMN %s int NOT NULL',
				$sqlHelper->quote($tableName),
				$sqlHelper->quote('PARENT_ID')
			));
		}
	}

	public static function getValidationForPrimary()
	{
		return [
			new Main\Entity\Validator\Length(null, 30)
		];
	}

    public static function validateHash()
    {
        return [
            new Main\Entity\Validator\Length(null, 33)
        ];
    }

    public static function validateStatus()
    {
        return [
            new Main\Entity\Validator\Length(null, 1)
        ];
    }
}