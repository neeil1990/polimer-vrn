<?php

namespace Sotbit\Seometa;

class IblockProperty
{
    private static $arrIBlockProp = array();

    public function getIblockProp($id){
        if(!isset(self::$arrIBlockProp[$id]))
            self::$arrIBlockProp[$id] = \CIBlockProperty::GetByID($id)->fetch();

        return self::$arrIBlockProp[$id];
    }
}