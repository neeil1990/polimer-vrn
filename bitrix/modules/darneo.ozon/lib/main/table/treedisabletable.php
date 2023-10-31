<?php

namespace Darneo\Ozon\Main\Table;

use Bitrix\Main\Application;
use Bitrix\Main\ORM;
use Bitrix\Main\ORM\Data;
use Bitrix\Main\ORM\Fields;
use Darneo\Ozon\Import\Table\TreeTable;

class TreeDisableTable extends Data\DataManager
{
    public static string $tablePrefix = '';

    public static function getTableName(): string
    {
        if (self::$tablePrefix) {
            return 'darneo_ozon_data_tree_disable_' . self::$tablePrefix;
        }
        return 'darneo_ozon_data_tree_disable';
    }

    public static function setTablePrefix(string $tablePrefix): void
    {
        self::$tablePrefix = $tablePrefix;
        if ($tablePrefix) {
            $connection = Application::getConnection();
            $tableName = 'darneo_ozon_data_tree_disable_' . self::$tablePrefix;
            if (!$connection->isTableExists($tableName)) {
                self::getEntity()->createDbTable();
            }
        }
    }

    public static function getMap(): array
    {
        return [
            new Fields\StringField('CATEGORY_ID', ['primary' => true]),
            new Fields\BooleanField('DISABLE'),
        ];
    }

    public static function onBeforeAdd(ORM\Event $event): ORM\EventResult
    {
        $result = new ORM\EventResult();

        $data = $event->getParameter('fields');
        $categoryId = $data['CATEGORY_ID'];
        $disable = $data['DISABLE'];

        if ($disable) {
            self::disableChildTree($categoryId);
        }

        return $result;
    }

    private static function disableChildTree($categoryId): void
    {
        $parameters = [
            'filter' => ['PARENT_ID' => $categoryId],
            'select' => ['CATEGORY_ID'],
        ];

        $result = TreeTable::getList($parameters);
        while ($row = $result->fetch()) {
            if (!self::getById($row['CATEGORY_ID'])->fetch()) {
                self::add(['CATEGORY_ID' => $row['CATEGORY_ID'], 'DISABLE' => true]);
            }
        }
    }
}
