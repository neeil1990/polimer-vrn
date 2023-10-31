<?php

namespace Darneo\Ozon\Import\Product;

use Bitrix\Main\Application;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\Web\Json;
use Darneo\Ozon\Api\v3\Product;
use Darneo\Ozon\Import\Table\ConnectionOfferProductTable;
use Darneo\Ozon\Import\Table\ProductListTable;

class Step
{
    protected array $errors = [];

    public function getDataCount(): int
    {
        return ConnectionOfferProductTable::getCount();
    }

    public function initData(int $page = 1, int $limit = 100): void
    {
        if ($page === 1) {
            (new Connect())->start();
            $this->reinstallTable();
        }

        $productIds = [];
        $import = new \Darneo\Ozon\Api\v2\Product();

        $parameters = [
            'select' => [
                'PRODUCT_OZON_ID',
            ],
            'limit' => $limit,
            'offset' => ($limit * $page) - $limit,
        ];
        $result = ConnectionOfferProductTable::getList($parameters);
        while ($row = $result->fetch()) {
            $productId = $row['PRODUCT_OZON_ID'];
            $productIds[] = $productId;
            $data = $import->info($productId);
            if (!$data['result']) {
                $this->errors[] = Loc::getMessage(
                    'DARNEO_OZON_IMPORT_CORE_PRODUCT_ERROR_IMPORT',
                    [
                        '#PRODUCT_ID#' => $productId,
                        '#ANSWER#' => Json::encode($data),
                    ]
                );
                continue;
            }
            $dataResult = $data['result'];
            $resultProduct = ProductListTable::add(
                [
                    'ID' => $dataResult['id'],
                    'OFFER_ID' => $dataResult['offer_id'],
                    'NAME' => $dataResult['name'],
                    'STATUS_CODE' => $dataResult['status']['state'],
                    'STATUS_NAME' => $dataResult['status']['state_name'],
                    'CATEGORY_ID' => $dataResult['category_id'],
                    'IS_ERROR' => !empty($dataResult['status']['item_errors']),
                    'JSON' => $data['result'],
                ]
            );
            if (!$resultProduct->isSuccess()) {
                $this->errors[] = array_merge($this->errors, $resultProduct->getErrorMessages());
            }
        }
        if ($productIds) {
            $this->importStocks($productIds);
        }
    }

    private function reinstallTable(): void
    {
        $connection = Application::getConnection();
        $entitiesDataClasses = [
            ProductListTable::class
        ];
        /** @var DataManager $entityDataClass */
        foreach ($entitiesDataClasses as $entityDataClass) {
            if ($connection->isTableExists($entityDataClass::getTableName())) {
                $connection->dropTable($entityDataClass::getTableName());
            }
            $entityDataClass::getEntity()->createDbTable();
        }
    }

    private function importStocks(array $productIds): void
    {
        $import = new Product();
        $lastId = '';
        updateValue:
        $data = $import->infoStocks($productIds, $lastId);
        if ($data['result']['items']) {
            foreach ($data['result']['items'] as $item) {
                foreach ($item['stocks'] as $stock) {
                    switch ($stock['type']) {
                        case 'fbs':
                            ProductListTable::update(
                                $item['product_id'],
                                [
                                    'STOCK_FBS' => $stock['present'] ?: 0,
                                    'STOCK_FBS_RESERVED' => $stock['reserved'] ?: 0,
                                ]
                            );
                            break;
                        case 'fbo':
                            ProductListTable::update(
                                $item['product_id'],
                                [
                                    'STOCK_FBO' => $stock['present'] ?: 0,
                                    'STOCK_FBO_RESERVED' => $stock['reserved'] ?: 0,
                                ]
                            );
                            break;
                    }
                }
            }
            $lastId = $data['result']['last_id'];
            goto updateValue;
        }
    }
}
