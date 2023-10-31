<?php
namespace Wbs24\Ozonexport;

class CCatalogSku
{
    public function GetInfoByProductIBlock(...$args)
    {
        $result = \CCatalogSku::GetInfoByProductIBlock(...$args);
        //\Wbs24\Ozonexport::showReturnAsCode($result);
        return $result;
    }
}