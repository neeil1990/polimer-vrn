<?php

namespace Darneo\Ozon\Import\Core;

use CEventLog;
use Darneo\Ozon\Main\Table\TreeDisableTable;

abstract class Base
{
    protected array $errors = [];

    abstract public function start();

    public function __destruct()
    {
        foreach ($this->errors as $error) {
            $this->setLog($error);
        }
    }

    protected function setLog(string $text): void
    {
        CEventLog::Add(
            [
                'SEVERITY' => 'INFO',
                'AUDIT_TYPE_ID' => 'OZON_DATA_UPDATE',
                'MODULE_ID' => 'main',
                'ITEM_ID' => '',
                'DESCRIPTION' => $text,
            ]
        );
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    protected function getTreeDisable(): array
    {
        $rows = [];

        $result = TreeDisableTable::getList(
            [
                'filter' => ['DISABLE' => true],
                'select' => ['CATEGORY_ID'],
                'cache' => ['ttl' => 86400]
            ]
        );
        while ($row = $result->fetch()) {
            $rows[] = (int)$row['CATEGORY_ID'];
        }

        return $rows;
    }
}
