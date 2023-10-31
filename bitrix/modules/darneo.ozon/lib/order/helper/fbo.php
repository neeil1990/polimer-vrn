<?php

namespace Darneo\Ozon\Order\Helper;

use Bitrix\Main\Localization\Loc;

class Fbo
{
    public static function isStatusNew(string $statusCode): bool
    {
        return $statusCode === 'awaiting_deliver';
    }

    public static function isStatusError(string $statusCode): bool
    {
        return $statusCode === 'cancelled';
    }

    public static function isStatusFinish(string $statusCode): bool
    {
        return $statusCode === 'delivered';
    }

    public static function getStatusLoc(string $statusCode): string
    {
        switch ($statusCode) {
            case 'awaiting_packaging':
                return Loc::getMessage('DARNEO_OZON_ORDER_HELPER_FBS_STATUS_AWAITING_PACKAGING');
            case 'awaiting_deliver':
                return Loc::getMessage('DARNEO_OZON_ORDER_HELPER_FBS_STATUS_AWAITING_DELIVER');
            case 'delivering':
                return Loc::getMessage('DARNEO_OZON_ORDER_HELPER_FBS_STATUS_DELIVERING');
            case 'delivered':
                return Loc::getMessage('DARNEO_OZON_ORDER_HELPER_FBS_STATUS_DELIVERED');
            case 'cancelled':
                return Loc::getMessage('DARNEO_OZON_ORDER_HELPER_FBS_STATUS_CANCELLED');
        }

        return $statusCode;
    }
}