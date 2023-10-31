<?php

namespace Darneo\Ozon\Export;

use Bitrix\Main\Application;
use Bitrix\Main\Entity\Base;
use Darneo\Ozon\Export\Table\StockTmpTable;
use Darneo\Ozon\MultiBase;

class StockTmp extends MultiBase
{
    private string $tablePrefix;

    public function __construct($tablePrefix)
    {
        parent::__construct();
        $this->tablePrefix = $tablePrefix;
        $this->initTable();
    }

    public function initTable(): void
    {
        global $DB;
        $connection = Application::getConnection();
        if (!$connection->isTableExists($this->getTableName())) {
            $arSql = $this->entityTable->compileDbTableStructureDump();
            foreach ($arSql as $strSql) {
                $strSql = str_replace($this->entityTable->getDBTableName(), $this->getTableName(), $strSql);
                $DB->Query($strSql);
            }
        }
    }

    private function getTableName(): string
    {
        return $this->entityTable->getDBTableName() . '_' . $this->tablePrefix;
    }

    public function getList(array $parameters = [])
    {
        global $DB;

        $strSql = "SELECT BEN.* FROM {$this->getTableName()} BEN";

        if ($parameters['limit']) {
            $strSql .= ' LIMIT ' . $parameters['limit'];
        }

        if ($parameters['offset']) {
            $strSql .= ' OFFSET ' . $parameters['offset'];
        }

        return $DB->Query($strSql);
    }

    public function getCount(array $filter = []): int
    {
        global $DB;
        $strSql = "SELECT COUNT(*) as CNT FROM {$this->getTableName()}";
        $result = $DB->Query($strSql);

        if ($row = $result->Fetch()) {
            return $row['CNT'] ?: 0;
        }

        return 0;
    }

    public function add(array $data): int
    {
        global $DB;

        return $DB->Add($this->getTableName(), $data) ?: 0;
    }

    public function dropTable()
    {
        global $DB;
        $strSql = "DROP TABLE {$this->getTableName()}";

        return $DB->Query($strSql);
    }

    protected function getEntity(): Base
    {
        return StockTmpTable::getEntity();
    }
}