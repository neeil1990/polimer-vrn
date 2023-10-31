<?php

namespace Darneo\Ozon\Export\Helper;

use Bitrix\Main\Localization\Loc;

class AttributeType
{
    public const LIST = 'LIST';
    public const LIST_MULTI = 'LIST_MULTI';
    public const STRING = 'STRING';
    public const URL = 'URL';
    public const BOOLEAN = 'BOOLEAN';
    public const DECIMAL = 'DECIMAL';
    public const INTEGER = 'INTEGER';
    public const MULTILINE = 'MULTILINE';

    public static function getTypeHTML(string $type): string
    {
        $mess = '';
        switch ($type) {
            case self::LIST:
                $mess = Loc::getMessage('DARNEO_OZON_EXPORT_HELPER_ATTRIBUTE_LIST');
                break;
            case self::LIST_MULTI:
                $mess = Loc::getMessage('DARNEO_OZON_EXPORT_HELPER_ATTRIBUTE_LIST_MULTI');
                break;
            case self::STRING:
                $mess = Loc::getMessage('DARNEO_OZON_EXPORT_HELPER_ATTRIBUTE_STRING');
                break;
            case self::URL:
                $mess = Loc::getMessage('DARNEO_OZON_EXPORT_HELPER_ATTRIBUTE_URL');
                break;
            case self::BOOLEAN:
                $mess = Loc::getMessage('DARNEO_OZON_EXPORT_HELPER_ATTRIBUTE_BOOLEAN');
                break;
            case self::DECIMAL:
                $mess = Loc::getMessage('DARNEO_OZON_EXPORT_HELPER_ATTRIBUTE_DECIMAL');
                break;
            case self::INTEGER:
                $mess = Loc::getMessage('DARNEO_OZON_EXPORT_HELPER_ATTRIBUTE_INTEGER');
                break;
            case self::MULTILINE:
                $mess = Loc::getMessage('DARNEO_OZON_EXPORT_HELPER_ATTRIBUTE_MULTILINE');
                break;
        }

        return $mess;
    }
}
