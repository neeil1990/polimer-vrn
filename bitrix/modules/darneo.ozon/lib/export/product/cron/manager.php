<?php

namespace Darneo\Ozon\Export\Product\Cron;

use Bitrix\Main\Type;
use Darneo\Ozon\Api\v1\Product;
use Darneo\Ozon\Export\Table\ProductCronTable;
use Darneo\Ozon\Export\Table\ProductListTable;
use Darneo\Ozon\Export\Table\ProductLogTable;
use Darneo\Ozon\Import\Product\Connect;
use Darneo\Ozon\Import\Table\ConnectionOfferProductTable;

class Manager
{
    public function start(): void
    {
        (new Connect())->start();
        $this->importStatus();
        $list = $this->getProductList();
        foreach ($list as $elementId) {
            $cronLogId = ProductCronTable::add(['PRODUCT_ID' => $elementId])->getId();
            (new ProductItem($elementId))->start();
            ProductCronTable::update($cronLogId, ['DATE_FINISHED' => new Type\DateTime()]);
        }
        $this->importStatus();
    }

    public function importStatus(): void
    {
        $parameters = [
            'filter' => [
                'IS_FINISH' => false
            ],
            'select' => [
                'ID',
                'ANSWER'
            ],
            'order' => ['ID' => 'DESC']
        ];
        $result = ProductLogTable::getList($parameters);
        $info = (new Product());
        while ($row = $result->fetch()) {
            $taskId = $row['ANSWER']['result']['task_id'];
            if ($taskId) {
                $status = $info->importInfo($taskId);
                $isFinish = false;
                $isError = false;
                foreach ($status['result']['items'] as $item) {
                    $isFinish = true;
                    if ($item['errors']) {
                        $isError = true;
                    } else {
                        if (ConnectionOfferProductTable::getById($item['offer_id'])->fetch()) {
                            ConnectionOfferProductTable::update(
                                $item['offer_id'],
                                [
                                    'PRODUCT_OZON_ID' => $item['product_id']
                                ]
                            );
                        } else {
                            ConnectionOfferProductTable::add(
                                [
                                    'OFFER_ID' => $item['offer_id'],
                                    'PRODUCT_OZON_ID' => $item['product_id']
                                ]
                            );
                        }
                    }
                }
                ProductLogTable::update(
                    $row['ID'],
                    [
                        'ANSWER_JSON' => $status,
                        'IS_ERROR' => $isError,
                        'IS_FINISH' => $isFinish
                    ]
                );
            } else {
                ProductLogTable::update(
                    $row['ID'],
                    [
                        'IS_ERROR' => true,
                        'IS_FINISH' => true
                    ]
                );
            }
        }
    }

    private function getProductList(): array
    {
        $rows = [];
        $parameters = [
            'filter' => ['IS_CRON' => true],
            'select' => ['ID']
        ];
        $result = ProductListTable::getList($parameters);
        while ($row = $result->fetch()) {
            $rows[] = $row['ID'];
        }

        return $rows;
    }
}
