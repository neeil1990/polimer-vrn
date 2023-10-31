<?php
namespace Wbs24\Sbermmexport;

class CIBlock
{
    public function GetByID(...$args)
    {
        $result = \CIBlock::GetByID(...$args);

        return $result;
    }

    public function GetList(...$args)
    {
        return \CIBlock::GetList(...$args);
    }
}
