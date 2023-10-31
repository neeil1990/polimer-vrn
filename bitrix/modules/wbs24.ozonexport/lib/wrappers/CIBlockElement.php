<?php
namespace Wbs24\Ozonexport;

class CIBlockElement
{
    public function GetList(...$args)
    {
        $result = \CIBlockElement::GetList(...$args);
        //\Wbs24\Ozonexport::showReturnAsCode($result);
        return $result;
    }

    public function GetPropertyValuesArray(&$items, $a2, $a3, $a4, $a5)
    {
        \CIBlockElement::GetPropertyValuesArray($items, $a2, $a3, $a4, $a5);
        //\Wbs24\Ozonexport::showReturnAsCode($items);
        return $items;
    }

    public function GetProperty(...$args)
    {
        return \CIBlockElement::GetProperty(...$args);
    }
}
