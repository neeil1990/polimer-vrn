<?php

namespace Darneo\Ozon\Analytics\Import;

use Bitrix\Main\Type\Date;
use Bitrix\Main\Type\DateTime;
use Darneo\Ozon\Analytics\Table\SaleTable;
use Darneo\Ozon\Api\v1\Analytics;

class Sale
{
    private array $errors = [];

    public function init(): void
    {
        $dimension = ['day'];
        $metrics = ['revenue'];

        if ($this->isGetFull()) {
            $dateFrom = (new DateTime())->add('-5 years')->format('Y-m-d');
        } else {
            $dateFrom = ($this->getLastDay())->add('-1 day')->format('Y-m-d');
        }

        $dateTo = (new DateTime())->format('Y-m-d');

        $res = (new Analytics())->data($dateFrom, $dateTo, $dimension, $metrics);
        if ($res['result'] && $res['result']['data']) {
            foreach ($res['result']['data'] as $data) {
                $key = array_key_first($data['dimensions']);
                $date = $data['dimensions'][$key]['id'];
                $date = new DateTime($date, 'Y-m-d');

                $key = array_key_first($data['metrics']);
                $sum = $data['metrics'][$key] ?: 0;

                $rowId = $this->getRow($date);
                if ($rowId > 0) {
                    $this->updateSale($rowId, $date, $sum);
                } else {
                    $this->addSale($date, $sum);
                }
            }
        }
    }

    private function isGetFull(): bool
    {
        return SaleTable::getCount() === 0;
    }

    private function getLastDay(): Date
    {
        $parameters = [
            'select' => ['ID', 'DATE'],
            'order' => ['DATE' => 'DESC'],
            'limit' => 1
        ];
        $result = SaleTable::getList($parameters);
        if ($row = $result->fetch()) {
            if ($row['DATE'] instanceof Date) {
                return $row['DATE'];
            }
        }

        return new Date();
    }

    private function getRow(DateTime $date): int
    {
        $parameters = [
            'filter' => ['DATE' => $date],
            'select' => ['ID']
        ];
        $result = SaleTable::getList($parameters);
        if ($row = $result->fetch()) {
            return $row['ID'];
        }

        return 0;
    }

    private function updateSale(int $rowId, DateTime $date, int $sum): void
    {
        $result = SaleTable::update($rowId, ['DATE' => $date, 'SUM' => $sum]);
        if (!$result->isSuccess()) {
            $this->errors[] = $result->getErrorMessages();
        }

        array_merge(...$this->errors);
    }

    private function addSale(DateTime $date, int $sum): void
    {
        $result = SaleTable::add(['DATE' => $date, 'SUM' => $sum]);
        if (!$result->isSuccess()) {
            $this->errors[] = $result->getErrorMessages();
        }

        array_merge(...$this->errors);
    }
}