<?php

namespace Darneo\Ozon\Export\Price\Cron;

use Bitrix\Main\Type;
use Darneo\Ozon\Export\Table\PriceCronTable;
use Darneo\Ozon\Export\Table\PriceListTable;
use Darneo\Ozon\Import\Product\Connect;

class Manager
{
    public function start(): void
    {
        (new Connect())->start();
        $list = $this->getPriceList();
        foreach ($list as $elementId) {
            $cronLogId = PriceCronTable::add(['PRICE_ID' => $elementId])->getId();
            (new PriceItem($elementId))->start();
            PriceCronTable::update($cronLogId, ['DATE_FINISHED' => new Type\DateTime()]);
        }
    }

    private function getPriceList(): array
    {
        $rows = [];
        $parameters = [
            'filter' => ['IS_CRON' => true],
            'select' => ['ID']
        ];
        $result = PriceListTable::getList($parameters);
        while ($row = $result->fetch()) {
            $rows[] = $row['ID'];
        }

        return $rows;
    }
}