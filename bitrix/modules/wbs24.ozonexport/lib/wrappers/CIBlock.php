<?php
namespace Wbs24\Ozonexport;

class CIBlock
{
    public function GetByID(...$args)
    {
        $result = \CIBlock::GetByID(...$args);
        //\Wbs24\Ozonexport::showReturnAsCode($result);
        return $result;
    }

    public function GetList(...$args)
    {
        return \CIBlock::GetList(...$args);
    }
}
