<?php
namespace Wbs24\Sbermmexport;

class StoreTable
{
    public function getList(...$args)
    {
        return \Bitrix\Catalog\StoreTable::getList(...$args);
    }
}
