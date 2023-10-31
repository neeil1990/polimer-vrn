<?php

namespace Darneo\Ozon\Import\Warehouse;

use Bitrix\Main\Application;
use Bitrix\Main\Entity\DataManager;
use Darneo\Ozon\Api\v2\Warehouse;
use Darneo\Ozon\Import\Table\StockTable;

class Import
{
    private array $errors = [];

    public function initData(): bool
    {
        $this->deleteData();
        $import = new Warehouse();
        $data = $import->list();
        if ($data['result']) {
            foreach ($data['result'] as $datum) {
                $result = StockTable::add(
                    [
                        'ID' => $datum['warehouse_id'],
                        'NAME' => $datum['name'],
                        'IS_RFBS' => $datum['is_rfbs']
                    ]
                );
                if (!$result->isSuccess()) {
                    $this->errors[] = $result->getErrorMessages();
                }
            }
            $this->errors = array_merge(...$this->errors);
            return true;
        }

        if ($data['message']) {
            $this->errors[] = $data['message'];
        }

        return false;
    }

    private function deleteData(): void
    {
        $connection = Application::getConnection();
        $entitiesDataClasses = [
            StockTable::class
        ];
        /** @var DataManager $entityDataClass */
        foreach ($entitiesDataClasses as $entityDataClass) {
            if ($connection->isTableExists($entityDataClass::getTableName())) {
                $connection->dropTable($entityDataClass::getTableName());
            }
            $entityDataClass::getEntity()->createDbTable();
        }
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getDataCount(): int
    {
        return StockTable::getCount();
    }
}
