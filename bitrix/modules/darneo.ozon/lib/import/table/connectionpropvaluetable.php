<?php

namespace Darneo\Ozon\Import\Table;

use Bitrix\Main\Application;
use Bitrix\Main\ORM\Data;
use Bitrix\Main\ORM\Fields;
use Bitrix\Main\ORM\Fields\Validators;

class ConnectionPropValueTable extends Data\DataManager
{
    public static string $tablePrefix = '';

    public static function getTableName(): string
    {
        if (self::$tablePrefix) {
            return 'darneo_ozon_data_connection_prop_value_' . self::$tablePrefix;
        }
        return 'darneo_ozon_data_connection_prop_value';
    }

    public static function setTablePrefix(string $tablePrefix): void
    {
        self::$tablePrefix = $tablePrefix;
        if ($tablePrefix) {
            $connection = Application::getConnection();
            $tableName = 'darneo_ozon_data_connection_prop_value_' . self::$tablePrefix;
            if (!$connection->isTableExists($tableName)) {
                self::getEntity()->createDbTable();
                global $DB;
                $DB->Query(
                    'CREATE INDEX INDEX_1 ON ' . $tableName . '(PROPERTY_ID)'
                );
            }
        }
    }

    public static function getMap(): array
    {
        return [
            new Fields\IntegerField('ID', [
                'primary' => true,
                'autocomplete' => true,
                'validation' => static function () {
                    return [
                        new Validators\ForeignValidator(PropertyValueTable::getEntity()->getField('ID'))
                    ];
                }
            ]),
            new Fields\StringField('VALUE_ID', [
                'validation' => static function () {
                    return [
                        new Validators\ForeignValidator(PropertyValueTable::getEntity()->getField('ID'))
                    ];
                }
            ]),
            new Fields\Relations\Reference(
                'VALUE',
                PropertyValueTable::class,
                ['=this.VALUE_ID' => 'ref.ID'],
                ['join_type' => 'left']
            ),
            new Fields\StringField('PROPERTY_ID', [
                'validation' => static function () {
                    return [
                        new Validators\ForeignValidator(PropertyListTable::getEntity()->getField('ID'))
                    ];
                }
            ]),
        ];
    }

    public static function deleteTable(string $categoryId): void
    {
        global $DB;

        $tableName = "darneo_ozon_data_connection_prop_value_{$categoryId}";
        if (self::checkExistTable($tableName)) {
            $strSql = "DROP TABLE darneo_ozon_data_connection_prop_value_{$categoryId}";
            $DB->Query($strSql);
        }
    }

    public static function checkExistTable(string $tableName): bool
    {
        global $DB;
        $strSql = "SHOW TABLES LIKE '$tableName'";
        $errMess = '';
        $res = $DB->Query($strSql, false, $errMess . __LINE__);
        if ($row = $res->Fetch()) {
            return true;
        }

        return false;
    }
}
