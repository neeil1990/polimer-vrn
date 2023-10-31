<?php

namespace Darneo\Ozon\Import\Product;

use Bitrix\Main\Application;
use Bitrix\Main\ORM\Data\DataManager;
use Darneo\Ozon\Import\Table\ProductListTable;

class Manager extends Base
{
    public function __construct()
    {
        $tables = [
            ProductListTable::class
        ];

        $connection = Application::getConnection();
        /** @var DataManager $table */
        foreach ($tables as $table) {
            if ($connection->isTableExists($table::getTableName())) {
                $connection->dropTable($table::getTableName());
            }
            $table::getEntity()->createDbTable();
        }
    }

    public function start(): void
    {
        (new Connect())->start();
        (new Product())->start();
    }
}
