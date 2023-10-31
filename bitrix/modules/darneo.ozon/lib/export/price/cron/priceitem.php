<?php

namespace Darneo\Ozon\Export\Price\Cron;

use Darneo\Ozon\Api\v1\Product;
use Darneo\Ozon\Export\Price;
use Darneo\Ozon\Export\Table\PriceLogTable;

class PriceItem
{
    private int $settingId;
    private Price\Manager $manager;

    public function __construct(int $settingId)
    {
        $this->settingId = $settingId;
        $this->manager = new Price\Manager($this->settingId);
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

        $rowLog = [];
        foreach ($data as $elementId => $item) {
            $result = PriceLogTable::add(
                [
                    'PRICE_ID' => $this->settingId,
                    'ELEMENT_ID' => $elementId,
                    'OFFER_ID' => $item['offer_id'],
                    'SEND_JSON' => $item,
                    'ANSWER' => [],
                ]
            );
            $rowLog[$item['offer_id']] = $result->getId();
        }
        $data = array_values($data);

        $answer = (new Product())->importPrices($data);
        if ($answer['result']) {
            foreach ($answer['result'] as $datum) {
                $rowLogId = $rowLog[$datum['offer_id']];
                PriceLogTable::update($rowLogId, ['ANSWER' => $datum, 'IS_ERROR' => count($datum['errors'])]);
            }
        } else {
            foreach ($rowLog as $rowLogId) {
                PriceLogTable::update($rowLogId, ['ANSWER' => $answer, 'IS_ERROR' => true]);
            }
        }

        $isFinish = $page * $limit >= $totalCount;
        if (!$isFinish) {
            $page++;
            goto startExportMain;
        }
    }
}