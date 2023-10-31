<?php

namespace Darneo\Ozon\Export\Table;

use Bitrix\Iblock\ElementTable;
use Bitrix\Main\Application;
use Bitrix\Main\ORM\Data;
use Bitrix\Main\ORM\Fields;
use Bitrix\Main\ORM\Fields\Validators;
use Darneo\Ozon\EventHandlers;

class ProductTmpTable extends Data\DataManager
{
    public static string $tablePrefix = '';

    public static function getTableName(): string
    {
        if (self::$tablePrefix) {
            return 'darneo_ozon_export_product_tmp_' . self::$tablePrefix;
        }
        return 'darneo_ozon_export_product_tmp';
    }

    public static function setTablePrefix(string $tablePrefix): void
    {
        self::$tablePrefix = $tablePrefix;
        if ($tablePrefix) {
            $connection = Application::getConnection();
            $tableName = 'darneo_ozon_export_product_tmp_' . self::$tablePrefix;
            if (!$connection->isTableExists($tableName)) {
                self::getEntity()->createDbTable();
            }
        }
    }

    public static function getMap(): array
    {
        return [
            new Fields\IntegerField('ELEMENT_ID', [
                'primary' => true,
                'validation' => static function () {
                    return [
                        new Validators\ForeignValidator(ElementTable::getEntity()->getField('ID'))
                    ];
                },
            ]),
        ];
    }
}
