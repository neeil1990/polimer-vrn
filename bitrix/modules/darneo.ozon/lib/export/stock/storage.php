<?php

namespace Darneo\Ozon\Export\Stock;

use Bitrix\Catalog;

class Storage extends Base
{
    public function get(): string
    {
        return $this->getStoreProduct();
    }

    private function getStoreProduct(): int
    {
        $count = 0;
        $result = Catalog\StoreProductTable::getList(
            [
                'select' => ['ID', 'STORE_ID', 'AMOUNT'],
                'filter' => [
                    'STORE_ID' => $this->settings['STORE_ID'],
                    'PRODUCT_ID' => $this->offerId ?: $this->elementId
                ]
            ]
        );
        while ($row = $result->fetch()) {
            $count += $row['AMOUNT'];
        }

        return $count;
    }

    public function getCountStorageAll(): int
    {
        $result = Catalog\ProductTable::getList(
            [
                'select' => ['ID', 'QUANTITY'],
                'filter' => [
                    'ID' => $this->offerId ?: $this->elementId
                ]
            ]
        );
        if ($row = $result->fetch()) {
            return $row['QUANTITY'] ?: 0;
        }

        return 0;
    }
}
