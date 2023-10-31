<?php
namespace Wbs24\Ozonexport;

class StoreProductTable
{
    public function getList(...$args)
    {
        $result = \Bitrix\Catalog\StoreProductTable::getList(...$args);
        //\Wbs24\Ozonexport::showReturnAsCode($result);
        return $result;
    }
}