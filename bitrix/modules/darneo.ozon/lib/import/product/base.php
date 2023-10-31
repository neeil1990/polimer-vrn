<?php

namespace Darneo\Ozon\Import\Product;

use CEventLog;

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
}
