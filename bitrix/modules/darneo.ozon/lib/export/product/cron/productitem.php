<?php

namespace Darneo\Ozon\Export\Product\Cron;

use Darneo\Ozon\Export\Product;
use Darneo\Ozon\Export\Table\ProductLogTable;

class ProductItem
{
    private int $settingId;
    private Product\Manager $manager;

    public function __construct(int $settingId)
    {
        $this->settingId = $settingId;
        $this->manager = new Product\Manager($this->settingId);
    }

    public function start(): void
    {
        $this->initDataTmp();
        $this->initSendOzon();
    }

    private function initDataTmp(): void
    {
        $page = 1;
        $limit = 100;
        $totalCount = $this->manager->getDataIblockCount();

        startExportTmp:
        $this->manager->initDataTmp($page, $limit);
        $isFinish = $page * $limit >= $totalCount;
        if (!$isFinish) {
            $page++;
            goto startExportTmp;
        }
    }

    private function initSendOzon(): void
    {
        $page = 1;
        $limit = 1000;
        $totalCount = $this->manager->getDataTmpCount();

        startExportMain:
        $data = $this->manager->getDataOzon($page, $limit);
        if (empty($data)) {
            return;
        }

        foreach ($data as $elementId => $item) {
            $answer = (new \Darneo\Ozon\Api\v2\Product())->import($item);
            ProductLogTable::add(
                [
                    'PRODUCT_ID' => $this->settingId,
                    'ELEMENT_ID' => $elementId,
                    'OFFER_ID' => $item['offer_id'],
                    'SEND_JSON' => $item,
                    'ANSWER' => $answer ?: [],
                ]
            );
        }

        $isFinish = $page * $limit >= $totalCount;
        if (!$isFinish) {
            $page++;
            goto startExportMain;
        }
    }
}
