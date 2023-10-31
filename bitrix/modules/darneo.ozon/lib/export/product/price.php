<?php

namespace Darneo\Ozon\Export\Product;

use Bitrix\Catalog\PriceTable;
use CCatalogProduct;

class Price extends Base
{
    private int $priceElementId;

    public function get(): array
    {
        $this->priceElementId = $this->offerId ?: $this->elementId;

        $filterPrice = [];
        if ($this->settings['TYPE_PRICE_ID']) {
            $filterPrice = $this->getPrice($this->settings['TYPE_PRICE_ID']);
        }
        $price = $this->getDiscountPrice($filterPrice);

        $basePrice = $price['RESULT_PRICE']['BASE_PRICE'];
        $discountPrice = $price['RESULT_PRICE']['DISCOUNT_PRICE'];

        if ($this->settings['PRICE_RATIO']) {
            $basePrice *= $this->settings['PRICE_RATIO'];
        }

        $discountPrice = $this->settings['IS_DISCOUNT_PRICE'] ? $discountPrice : $basePrice;
        $discountPrice = min($discountPrice, $basePrice);

        $vatRate = $price['RESULT_PRICE']['VAT_RATE'] ?: 0;

        return [
            'BASE_PRICE' => (string)$basePrice,
            'DISCOUNT_PRICE' => (string)$discountPrice,
            'VAT_RATE' => (string)$vatRate,
        ];
    }

    private function getPrice(int $priceId): array
    {
        $rows = [];
        $parameters = [
            'filter' => [
                'CATALOG_GROUP_ID' => $priceId,
                'PRODUCT_ID' => $this->priceElementId
            ],
            'select' => [
                'ID',
                'PRICE',
                'CURRENCY',
                'CATALOG_GROUP_ID',
            ],
        ];
        $result = PriceTable::getList($parameters);
        if ($row = $result->fetch()) {
            $rows[] = $row;
        }

        if (empty($rows)) {
            $rows[] = ['CATALOG_GROUP_ID' => $priceId];
        }

        return $rows;
    }

    private function getDiscountPrice(array $filterPrice = [])
    {
        $cnt = 1;
        $renewal = 'N';

        $arPrice = CCatalogProduct::GetOptimalPrice(
            $this->priceElementId,
            $cnt,
            [],
            $renewal,
            $filterPrice,
            $this->settings['SITE_ID']
        );
        if (!$arPrice || count($arPrice) <= 0) {
            if ($nearestQuantity = CCatalogProduct::GetNearestQuantityPrice($this->priceElementId, $cnt)) {
                $cnt = $nearestQuantity;
                $arPrice = CCatalogProduct::GetOptimalPrice(
                    $this->priceElementId,
                    $cnt,
                    [],
                    $renewal,
                    $filterPrice,
                    $this->settings['SITE_ID']
                );
            }
        }

        return $arPrice;
    }
}
