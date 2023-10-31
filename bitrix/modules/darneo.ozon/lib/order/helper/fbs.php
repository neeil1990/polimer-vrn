<?php

namespace Darneo\Ozon\Order\Helper;

use Bitrix\Main\Localization\Loc;

class Fbs
{
    public static function isStatusNew(string $statusCode): bool
    {
        return in_array($statusCode, ['awaiting_registration', 'awaiting_deliver'], true);
    }

    public static function isStatusError(string $statusCode): bool
    {
        return in_array($statusCode, ['cancelled', 'arbitration', 'client_arbitration', 'not_accepted'], true);
    }

    public static function isStatusFinish(string $statusCode): bool
    {
        return $statusCode === 'delivered';
    }

    public static function getStatusLoc(string $statusCode): string
    {
        switch ($statusCode) {
            case 'awaiting_registration':
                return Loc::getMessage('DARNEO_OZON_ORDER_HELPER_FBS_STATUS_AWAITING_REGISTRATION');
            case 'acceptance_in_progress':
                return Loc::getMessage('DARNEO_OZON_ORDER_HELPER_FBS_STATUS_ACCEPTANCE_IN_PROGRESS');
            case 'awaiting_approve':
                return Loc::getMessage('DARNEO_OZON_ORDER_HELPER_FBS_STATUS_AWAITING_APPROVE');
            case 'awaiting_packaging':
                return Loc::getMessage('DARNEO_OZON_ORDER_HELPER_FBS_STATUS_AWAITING_PACKAGING');
            case 'awaiting_deliver':
                return Loc::getMessage('DARNEO_OZON_ORDER_HELPER_FBS_STATUS_AWAITING_DELIVER');
            case 'arbitration':
                return Loc::getMessage('DARNEO_OZON_ORDER_HELPER_FBS_STATUS_ARBITRATION');
            case 'client_arbitration':
                return Loc::getMessage('DARNEO_OZON_ORDER_HELPER_FBS_STATUS_CLIENT_ARBITRATION');
            case 'delivering':
                return Loc::getMessage('DARNEO_OZON_ORDER_HELPER_FBS_STATUS_DELIVERING');
            case 'driver_pickup':
                return Loc::getMessage('DARNEO_OZON_ORDER_HELPER_FBS_STATUS_DRIVER_PICKUP');
            case 'delivered':
                return Loc::getMessage('DARNEO_OZON_ORDER_HELPER_FBS_STATUS_DELIVERED');
            case 'cancelled':
                return Loc::getMessage('DARNEO_OZON_ORDER_HELPER_FBS_STATUS_CANCELLED');
            case 'not_accepted':
                return Loc::getMessage('DARNEO_OZON_ORDER_HELPER_FBS_STATUS_NOT_ACCEPTED');
            case 'sent_by_seller':
                return Loc::getMessage('DARNEO_OZON_ORDER_HELPER_FBS_STATUS_SENT_BY_SELLER');
        }

        return $statusCode;
    }
}