<?php
namespace Wbs24\Sbermmexport;

class StoreProductTable
{
    public function getList(...$args)
    {
        return \Bitrix\Catalog\StoreProductTable::getList(...$args);
    }
}
