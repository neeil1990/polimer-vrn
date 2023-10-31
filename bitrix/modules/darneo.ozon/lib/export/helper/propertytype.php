<?php

namespace Darneo\Ozon\Export\Helper;

use Bitrix\Iblock\PropertyTable;
use Bitrix\Main\Localization\Loc;

class PropertyType
{
    public static function getTypeHTML(string $type): string
    {
        $mess = '';
        switch ($type) {
            case PropertyTable::TYPE_FILE:
                $mess = Loc::getMessage('DARNEO_OZON_EXPORT_HELPER_PROPERTY_TYPE_FILE');
                break;
            case PropertyTable::TYPE_ELEMENT:
                $mess = Loc::getMessage('DARNEO_OZON_EXPORT_HELPER_PROPERTY_TYPE_ELEMENT');
                break;
            case PropertyTable::TYPE_SECTION:
                $mess = Loc::getMessage('DARNEO_OZON_EXPORT_HELPER_PROPERTY_TYPE_SECTION');
                break;
            case PropertyTable::TYPE_LIST:
                $mess = Loc::getMessage('DARNEO_OZON_EXPORT_HELPER_PROPERTY_TYPE_LIST');
                break;
            case PropertyTable::TYPE_STRING:
                $mess = Loc::getMessage('DARNEO_OZON_EXPORT_HELPER_PROPERTY_TYPE_STRING');
                break;
            case PropertyTable::TYPE_NUMBER:
                $mess = Loc::getMessage('DARNEO_OZON_EXPORT_HELPER_PROPERTY_TYPE_NUMBER');
                break;
        }

        return $mess;
    }
}
