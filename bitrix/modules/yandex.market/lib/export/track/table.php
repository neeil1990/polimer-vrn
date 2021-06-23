<?php

namespace Yandex\Market\Export\Track;

use Bitrix\Main;
use Yandex\Market;

class Table extends Market\Reference\Storage\Table
{
    const ENTITY_TYPE_SETUP = 'setup';
    const ENTITY_TYPE_PROMO = 'promo';

    public static function getTableName()
    {
        return 'yamarket_export_track';
    }

    public static function createIndexes(Main\DB\Connection $connection)
    {
        $tableName = static::getTableName();

        $connection->createIndex($tableName, 'IX_' . $tableName . '_1', [ 'ENTITY_TYPE', 'ENTITY_ID' ]);
        $connection->createIndex($tableName, 'IX_' . $tableName . '_2', [ 'SOURCE_TYPE' ]);
    }

    public static function getMap()
    {
        return [
            new Main\Entity\IntegerField('ID', [
                'autocomplete' => true,
                'primary' => true
            ]),
            new Main\Entity\EnumField('ENTITY_TYPE', [
                'required' => true,
                'values' => [
                    static::ENTITY_TYPE_SETUP,
                    static::ENTITY_TYPE_PROMO
                ]
            ]),
            new Main\Entity\IntegerField('ENTITY_ID', [
                'required' => true
            ]),
            new Main\Entity\StringField('SOURCE_TYPE', [
                'required' => true,
                'validation' => [__CLASS__, 'validateSourceType']
            ]),
            new Main\Entity\StringField(
            	'SOURCE_PARAMS',
	            Market\Reference\Storage\Field\Serializer::getParameters()
            ),
        ];
    }

	public static function validateSourceType()
    {
        return [
            new Main\Entity\Validator\Length(null, 40)
        ];
    }
}