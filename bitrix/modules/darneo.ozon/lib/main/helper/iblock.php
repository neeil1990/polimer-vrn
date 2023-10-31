<?php

namespace Darneo\Ozon\Main\Helper;

use Bitrix\Catalog\CatalogIblockTable;
use Bitrix\Iblock\ElementTable;

class Iblock
{
    public static function getOfferIblockId(int $iblockId): int
    {
        $parameters = [
            'filter' => ['PRODUCT_IBLOCK_ID' => $iblockId],
            'select' => ['IBLOCK_ID'],
            'cache' => ['ttl' => 86400]
        ];
        $result = CatalogIblockTable::getList($parameters);
        if ($row = $result->fetch()) {
            return $row['IBLOCK_ID'];
        }

        return 0;
    }

    public static function getIblockIdByElementId(int $elementId): int
    {
        $parameters = [
            'filter' => ['ID' => $elementId],
            'select' => ['IBLOCK_ID'],
            'cache' => ['ttl' => 86400]
        ];
        $result = ElementTable::getList($parameters);
        if ($row = $result->fetch()) {
            return $row['IBLOCK_ID'];
        }

        return 0;
    }
}
