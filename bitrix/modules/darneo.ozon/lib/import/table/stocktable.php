<?php

namespace Darneo\Ozon\Import\Table;

use Bitrix\Main\Application;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM;
use Bitrix\Main\ORM\Data;
use Bitrix\Main\ORM\Fields;

class StockTable extends Data\DataManager
{
    public static string $tablePrefix = '';

    public static function getTableName(): string
    {
        if (self::$tablePrefix) {
            return 'darneo_ozon_data_stock_list_' . self::$tablePrefix;
        }
        return 'darneo_ozon_data_stock_list';
    }

    public static function setTablePrefix(string $tablePrefix): void
    {
        self::$tablePrefix = $tablePrefix;
        if ($tablePrefix) {
            $connection = Application::getConnection();
            $tableName = 'darneo_ozon_data_stock_list_' . self::$tablePrefix;
            if (!$connection->isTableExists($tableName)) {
                self::getEntity()->createDbTable();
            }
        }
    }

    public static function getMap(): array
    {
        return [
            new Fields\StringField('ID', [
                'primary' => true,
                'title' => Loc::getMessage('DARNEO_OZON_IMPORT_TABLE_STOCK_LIST_ID')
            ]),
            new Fields\StringField('NAME', [
                'required' => true,
                'title' => Loc::getMessage('DARNEO_OZON_IMPORT_TABLE_STOCK_LIST_NAME')
            ]),
            new Fields\BooleanField('IS_RFBS', [
                'required' => false,
                'title' => Loc::getMessage('DARNEO_OZON_IMPORT_TABLE_STOCK_LIST_IS_RFBS')
            ]),
        ];
    }

    public static function onBeforeAdd(ORM\Event $event): void
    {
        $elementId = $event->getParameter('fields')['ID'];
        $res = self::getById($elementId);
        if ($row = $res->fetch()) {
            self::delete($row['ID']);
        }
    }
}
