<?php

namespace Darneo\Ozon\Export\Stock\Cron;

use Bitrix\Main\Type;
use Darneo\Ozon\Export\Table\StockCronTable;
use Darneo\Ozon\Export\Table\StockListTable;
use Darneo\Ozon\Import\Product\Connect;

class Manager
{
    public function start(): void
    {
        (new Connect())->start();
        $list = $this->getStockList();
        foreach ($list as $elementId) {
            $cronLogId = StockCronTable::add(['STOCK_ID' => $elementId])->getId();
            (new StockItem($elementId))->start();
            StockCronTable::update($cronLogId, ['DATE_FINISHED' => new Type\DateTime()]);
        }
    }

    private function getStockList(): array
    {
        $rows = [];
        $parameters = [
            'filter' => ['IS_CRON' => true],
            'select' => ['ID']
        ];
        $result = StockListTable::getList($parameters);
        while ($row = $result->fetch()) {
            $rows[] = $row['ID'];
        }

        return $rows;
    }
}