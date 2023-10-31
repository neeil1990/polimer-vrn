<?php
namespace Wbs24\Ozonexport;

class StoreTable
{
    public function getList(...$args)
    {
        return \Bitrix\Catalog\StoreTable::getList(...$args);
    }
}
