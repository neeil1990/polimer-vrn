<?php

namespace Darneo\Ozon\Import\Table;

use Bitrix\Main\Application;
use Bitrix\Main\ORM\Data;
use Bitrix\Main\ORM\Fields;

class ConnectionOfferProductTable extends Data\DataManager
{
    public static string $tablePrefix = '';

    public static function getTableName(): string
    {
        if (self::$tablePrefix) {
            return 'darneo_ozon_data_connection_offer_product_' . self::$tablePrefix;
        }
        return 'darneo_ozon_data_connection_offer_product';
    }

    public static function setTablePrefix(string $tablePrefix): void
    {
        self::$tablePrefix = $tablePrefix;
        if ($tablePrefix) {
            $connection = Application::getConnection();
            $tableName = 'darneo_ozon_data_connection_offer_product_' . self::$tablePrefix;
            if (!$connection->isTableExists($tableName)) {
                self::getEntity()->createDbTable();
            }
        }
    }

    public static function getMap(): array
    {
        return [
            new Fields\StringField('OFFER_ID', ['primary' => true]),
            new Fields\StringField('PRODUCT_OZON_ID', ['required' => true]),
        ];
    }
}
