<?php

namespace Darneo\Ozon\Export\Helper;

use Bitrix\Main\Localization\Loc;
use Darneo\Ozon\Export\Product;

class Dimension
{
    public static function getTypeHTML(string $type): string
    {
        $mess = '';
        switch ($type) {
            case Product\Dimension::DIMENSION_UNIT_MM:
                $mess = Loc::getMessage('DARNEO_OZON_EXPORT_HELPER_DIMENSION_DIMENSION_UNIT_MM');
                break;
            case Product\Dimension::DIMENSION_UNIT_CM:
                $mess = Loc::getMessage('DARNEO_OZON_EXPORT_HELPER_DIMENSION_DIMENSION_UNIT_CM');
                break;
            case Product\Dimension::DIMENSION_UNIT_IN:
                $mess = Loc::getMessage('DARNEO_OZON_EXPORT_HELPER_DIMENSION_DIMENSION_UNIT_IN');
                break;
            case Product\Dimension::WEIGHT_UNIT_G:
                $mess = Loc::getMessage('DARNEO_OZON_EXPORT_HELPER_DIMENSION_WEIGHT_UNIT_G');
                break;
            case Product\Dimension::WEIGHT_UNIT_KG:
                $mess = Loc::getMessage('DARNEO_OZON_EXPORT_HELPER_DIMENSION_WEIGHT_UNIT_KG');
                break;
            case Product\Dimension::WEIGHT_UNIT_LB:
                $mess = Loc::getMessage('DARNEO_OZON_EXPORT_HELPER_DIMENSION_WEIGHT_UNIT_LB');
                break;
        }

        return $mess;
    }
}