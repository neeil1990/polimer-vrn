<?php

namespace Darneo\Ozon\Import;

use Bitrix\Main\Application;
use Bitrix\Main\Entity\Base;
use CIBlock;
use Darneo\Ozon\Import\Table\ConnectionPropValueTable;
use Darneo\Ozon\MultiBase;

class ConnectionPropValue extends MultiBase
{
    private string $tablePrefix;

    public function __construct($tablePrefix)
    {
        parent::__construct();
        $this->tablePrefix = $tablePrefix;
        $this->initTable();
    }

    private function initTable(): void
    {
        global $DB;
        $connection = Application::getConnection();
        if (!$connection->isTableExists($this->getTableName())) {
            $arSql = $this->entityTable->compileDbTableStructureDump();
            foreach ($arSql as $strSql) {
                $strSql = str_replace($this->entityTable->getDBTableName(), $this->getTableName(), $strSql);
                $DB->Query($strSql);
                $DB->Query("CREATE INDEX INDEX_1 ON {$this->getTableName()} (PROPERTY_ID)");
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

        // FILTER
        $arSqlSearch = [];
        foreach ($parameters['filter'] as $key => $val) {
            if ($key[0] === '!') {
                $key = mb_substr($key, 1);
                $bInvert = true;
            } else {
                $bInvert = false;
            }

            $key = mb_strtoupper($key);

            switch ($key) {
                case 'ID':
                    $arSqlSearch[] = CIBlock::FilterCreate('BEN.' . $key, $val, 'number', $bInvert);
                    break;
                case 'PROPERTY_ID':
                case 'VALUE_ID':
                    $arSqlSearch[] = CIBlock::FilterCreate('BEN.' . $key, $val, 'string_equal', $bInvert);
                    break;
            }
        }
        $strSqlSearch = implode(' AND ', array_filter($arSqlSearch));

        if ($strSqlSearch) {
            $strSql = "SELECT BEN.* FROM {$this->getTableName()} BEN WHERE $strSqlSearch";
        } else {
            $strSql = "SELECT BEN.* FROM {$this->getTableName()} BEN";
        }

        return $DB->Query($strSql);
    }

    public function add(array $data): int
    {
        global $DB;

        return $DB->Add($this->getTableName(), $data) ?: 0;
    }

    public function delete(int $id): bool
    {
        return false;
    }

    public function dropTable()
    {
        global $DB;
        $strSql = "DROP TABLE {$this->getTableName()}";

        return $DB->Query($strSql);
    }

    protected function getEntity(): Base
    {
        return ConnectionPropValueTable::getEntity();
    }
}