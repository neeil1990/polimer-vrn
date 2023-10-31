<?php

namespace Darneo\Ozon\Export\Table;

use Bitrix\Main\Application;
use Bitrix\Main\ORM\Data;
use Bitrix\Main\ORM\Fields;
use Darneo\Ozon\EventHandlers;

class PriceFilterTable extends Data\DataManager
{
    public static string $tablePrefix = '';

    public static function getTableName(): string
    {
        if (self::$tablePrefix) {
            return 'darneo_ozon_export_price_filter_' . self::$tablePrefix;
        }
        return 'darneo_ozon_export_price_filter';
    }

    public static function setTablePrefix(string $tablePrefix): void
    {
        self::$tablePrefix = $tablePrefix;
        if ($tablePrefix) {
            $connection = Application::getConnection();
            $tableName = 'darneo_ozon_export_price_filter_' . self::$tablePrefix;
            if (!$connection->isTableExists($tableName)) {
                self::getEntity()->createDbTable();
            }
        }
    }

    public static function getMap(): array
    {
        return [
            new Fields\IntegerField('ID', [
                'primary' => true,
                'autocomplete' => true,
            ]),
            new Fields\IntegerField('ELEMENT_ID', [
                'required' => true,
            ]),
            new Fields\IntegerField('PROP_ID', [
                'required' => true,
            ]),

            new Fields\StringField('COMPARE_TYPE', [
                'required' => true,
            ]),
            new Fields\StringField('COMPARE_VALUE', [
                'required' => false,
            ]),
        ];
    }
}
