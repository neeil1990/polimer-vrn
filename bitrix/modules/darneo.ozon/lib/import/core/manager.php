<?php

namespace Darneo\Ozon\Import\Core;

use Bitrix\Main\Application;
use Bitrix\Main\ORM\Data\DataManager;
use Darneo\Ozon\Import\Table\ConnectionPropCategoryTable;
use Darneo\Ozon\Import\Table\ConnectionPropValueTable;
use Darneo\Ozon\Import\Table\PropertyGroupTable;
use Darneo\Ozon\Import\Table\PropertyListTable;
use Darneo\Ozon\Import\Table\PropertyValueTable;
use Darneo\Ozon\Import\Table\TreeTable;

class Manager extends Base
{
    public function __construct()
    {
        $tables = [
            ConnectionPropCategoryTable::class,
            ConnectionPropValueTable::class,
            PropertyGroupTable::class,
            PropertyListTable::class,
            PropertyValueTable::class,
            TreeTable::class
        ];

        $categoryDelete = $this->getLevel3();
        foreach ($categoryDelete as $category) {
            ConnectionPropValueTable::deleteTable($category);
        }

        $connection = Application::getConnection();
        /** @var DataManager $table */
        foreach ($tables as $table) {
            if ($connection->isTableExists($table::getTableName())) {
                $connection->dropTable($table::getTableName());
            }
            $table::getEntity()->createDbTable();
        }
    }

    private function getLevel3(): array
    {
        $rows = [];
        $disable = $this->getTreeDisable();
        $parameters = ['select' => ['CATEGORY_ID'], 'filter' => ['LEVEL' => 3, '!=CATEGORY_ID' => $disable]];
        $result = TreeTable::getList($parameters);
        while ($row = $result->fetch()) {
            $rows[] = $row['CATEGORY_ID'];
        }

        return $rows;
    }

    public function start(): void
    {
        (new Category())->start();
        (new Attribute())->start();
        (new AttributeValue())->start();
    }
}
