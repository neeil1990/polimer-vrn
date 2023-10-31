<?php
namespace Wbs24\Sbermmexport;

class CIBlockElement
{
    public function GetList(...$args)
    {
        return \CIBlockElement::GetList(...$args);
    }

    public function GetPropertyValuesArray(&$items, $a2, $a3, $a4, $a5)
    {
        \CIBlockElement::GetPropertyValuesArray($items, $a2, $a3, $a4, $a5);

        return $items;
    }

    public function GetProperty(...$args)
    {
        return \CIBlockElement::GetProperty(...$args);
    }

    public function GetPropertyValues(...$args)
    {
        return \CIBlockElement::GetPropertyValues(...$args);
    }
}
