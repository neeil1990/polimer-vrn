<?php

namespace Darneo\Ozon\Export\Product;

use Bitrix\Catalog\CatalogIblockTable;
use Darneo\Ozon\Export\Table\ProductListTable;

abstract class Base
{
    protected int $iblockCatalogId;
    protected int $iblockOffersId;
    protected int $elementId;
    protected int $offerId;
    protected array $settings;

    public function __construct(int $exportProductId, int $elementId, int $offerId = 0)
    {
        $this->settings = $this->getSettings($exportProductId);
        $this->iblockCatalogId = $this->settings['IBLOCK_ID'] ?: 0;
        $this->iblockOffersId = $this->getOffersIblockId($this->iblockCatalogId);
        $this->elementId = $elementId;
        $this->offerId = $offerId;
    }

    private function getSettings(int $exportProductId): array
    {
        $rows = [];
        $parameters = [
            'filter' => ['ID' => $exportProductId],
            'select' => [
                'ID',
                'IBLOCK_ID',
                'PHOTO_MAIN',
                'PHOTO_OTHER',
                'VENDOR_CODE',
                'BAR_CODE',
                'DOMAIN',
                'TYPE_PRICE_ID',
                'SITE_ID',
                'IS_DISCOUNT_PRICE',
                'PRICE_RATIO',
            ],
            'cache' => ['ttl' => 86400]
        ];
        $result = ProductListTable::getList($parameters);
        if ($row = $result->fetch()) {
            $rows = $row;
        }

        return $rows;
    }

    private function getOffersIblockId(int $iblockId): int
    {
        $parameters = [
            'filter' => [
                'PRODUCT_IBLOCK_ID' => $iblockId
            ],
            'select' => ['IBLOCK_ID'],
            'cache' => ['ttl' => 86400]
        ];
        $result = CatalogIblockTable::getList($parameters);
        if ($row = $result->fetch()) {
            return $row['IBLOCK_ID'];
        }

        return 0;
    }

    abstract public function get();
}