<?php

namespace Darneo\Ozon\Fields\Value\Type;

use Bitrix\Catalog\StoreTable;
use Darneo\Ozon\Fields\Value\Base;
use Darneo\Ozon\Main\Helper\Encoding as HelpersEncoding;

class Stock extends Base
{
    public function get(): string
    {
        $rawValue = $this->getRaw();

        $store = $rawValue ? $this->getStore($rawValue) : [];
        $store = implode(', ', $store);
        $value = HelpersEncoding::toUtf($store);

        return $value;
    }

    private function getStore(array $storeIds): array
    {
        $rows = [];
        $parameters = [
            'filter' => ['ID' => $storeIds],
            'select' => ['ID', 'TITLE'],
            'order' => ['ID' => 'ASC'],
            'cache' => ['ttl' => 86400]
        ];
        $result = StoreTable::getList($parameters);
        while ($row = $result->fetch()) {
            $rows[] = $row['TITLE'];
        }

        return $rows;
    }
}
