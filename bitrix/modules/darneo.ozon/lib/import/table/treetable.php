<?php

namespace Darneo\Ozon\Import\Table;

use Bitrix\Main\Application;
use Bitrix\Main\ORM;
use Bitrix\Main\ORM\Data;
use Bitrix\Main\ORM\Fields;
use Darneo\Ozon\Main\Table\TreeDisableTable;

class TreeTable extends Data\DataManager
{
    public static string $tablePrefix = '';

    public static function getTableName(): string
    {
        if (self::$tablePrefix) {
            return 'darneo_ozon_data_tree_' . self::$tablePrefix;
        }
        return 'darneo_ozon_data_tree';
    }

    public static function setTablePrefix(string $tablePrefix): void
    {
        self::$tablePrefix = $tablePrefix;
        if ($tablePrefix) {
            $connection = Application::getConnection();
            $tableName = 'darneo_ozon_data_tree_' . self::$tablePrefix;
            if (!$connection->isTableExists($tableName)) {
                self::getEntity()->createDbTable();
            }
        }
    }

    public static function getMap(): array
    {
        return [
            new Fields\StringField('CATEGORY_ID', ['primary' => true]),
            new Fields\StringField('PARENT_ID'),
            new Fields\IntegerField('LEVEL'),
            new Fields\StringField('TITLE'),
            new Fields\Relations\Reference(
                'ACTIVE',
                TreeDisableTable::class,
                ['=this.CATEGORY_ID' => 'ref.CATEGORY_ID'],
                ['join_type' => 'left']
            )
        ];
    }

    public static function onBeforeAdd(ORM\Event $event): ORM\EventResult
    {
        $result = new ORM\EventResult();

        $data = $event->getParameter('fields');
        $parentId = $event->getParameter('fields')['PARENT_ID'] ?: 0;
        $level = self::getLevel($parentId);
        $data['LEVEL'] = $level;
        $result->modifyFields($data);

        if (!TreeDisableTable::getById($data['CATEGORY_ID'])->fetch()) {
            TreeDisableTable::add(['CATEGORY_ID' => $data['CATEGORY_ID'], 'DISABLE' => true]);
        }

        return $result;
    }

    public static function getLevel(int $parentId): int
    {
        $level = 1;

        count:
        $parameters = [
            'select' => ['CATEGORY_ID', 'PARENT_ID'],
            'filter' => ['CATEGORY_ID' => $parentId]
        ];

        $result = self::getList($parameters);
        if ($row = $result->fetch()) {
            $level++;
            if ($row['PARENT_ID']) {
                $parentId = $row['PARENT_ID'];
                goto count;
            }
        }

        return $level;
    }
}
