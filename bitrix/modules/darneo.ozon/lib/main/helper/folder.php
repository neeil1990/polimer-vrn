<?php

namespace Darneo\Ozon\Main\Helper;

class Folder
{
    public const PATH_CATALOG_PRODUCT = 'ozon/settings/catalog/product/';
    public const PATH_CATALOG_PRICE = 'ozon/settings/catalog/price/';
    public const PATH_CATEGORY_IBLOCK = 'ozon/settings/category/iblock/';

    public static function getPathEntity($entityName, string $folder = ''): string
    {
        switch ($entityName) {
            case 'CatalogProduct':
                $path = self::PATH_CATALOG_PRODUCT;
                break;
            case 'CatalogPrice':
                $path = self::PATH_CATALOG_PRICE;
                break;
            case 'CategoryIblock':
                $path = self::PATH_CATEGORY_IBLOCK;
                break;
            default:
                return '';
        }
        $path = $folder ? $path . $folder . '/' : $path;

        return self::get($path);
    }

    public static function get($path): string
    {
        $dirSite = self::getDirSite();

        return $dirSite . $path;
    }

    public static function getDirSite(): string
    {
        return '/_admin/';
    }
}
