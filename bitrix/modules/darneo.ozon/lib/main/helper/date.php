<?php

namespace Darneo\Ozon\Main\Helper;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type;
use DateTimeZone;

class Date
{
    public static function getFromImport(string $dateApi): Type\DateTime
    {
        return (new Type\DateTime($dateApi, 'Y-m-d\TH:i:s\Z', new DateTimeZone('UTC')));
    }

    public static function getRussianMonthName($shortVersion = false): array
    {
        if (!$shortVersion) {
            return [
                Loc::getMessage('DARNEO_OZON_MAIN_HELPER_DATE_MONTH_FULL_1'),
                Loc::getMessage('DARNEO_OZON_MAIN_HELPER_DATE_MONTH_FULL_2'),
                Loc::getMessage('DARNEO_OZON_MAIN_HELPER_DATE_MONTH_FULL_3'),
                Loc::getMessage('DARNEO_OZON_MAIN_HELPER_DATE_MONTH_FULL_4'),
                Loc::getMessage('DARNEO_OZON_MAIN_HELPER_DATE_MONTH_FULL_5'),
                Loc::getMessage('DARNEO_OZON_MAIN_HELPER_DATE_MONTH_FULL_6'),
                Loc::getMessage('DARNEO_OZON_MAIN_HELPER_DATE_MONTH_FULL_7'),
                Loc::getMessage('DARNEO_OZON_MAIN_HELPER_DATE_MONTH_FULL_8'),
                Loc::getMessage('DARNEO_OZON_MAIN_HELPER_DATE_MONTH_FULL_9'),
                Loc::getMessage('DARNEO_OZON_MAIN_HELPER_DATE_MONTH_FULL_10'),
                Loc::getMessage('DARNEO_OZON_MAIN_HELPER_DATE_MONTH_FULL_11'),
                Loc::getMessage('DARNEO_OZON_MAIN_HELPER_DATE_MONTH_FULL_12'),
            ];
        }

        return [
            Loc::getMessage('DARNEO_OZON_MAIN_HELPER_DATE_MONTH_SHORT_1'),
            Loc::getMessage('DARNEO_OZON_MAIN_HELPER_DATE_MONTH_SHORT_2'),
            Loc::getMessage('DARNEO_OZON_MAIN_HELPER_DATE_MONTH_SHORT_3'),
            Loc::getMessage('DARNEO_OZON_MAIN_HELPER_DATE_MONTH_SHORT_4'),
            Loc::getMessage('DARNEO_OZON_MAIN_HELPER_DATE_MONTH_SHORT_5'),
            Loc::getMessage('DARNEO_OZON_MAIN_HELPER_DATE_MONTH_SHORT_6'),
            Loc::getMessage('DARNEO_OZON_MAIN_HELPER_DATE_MONTH_SHORT_7'),
            Loc::getMessage('DARNEO_OZON_MAIN_HELPER_DATE_MONTH_SHORT_8'),
            Loc::getMessage('DARNEO_OZON_MAIN_HELPER_DATE_MONTH_SHORT_9'),
            Loc::getMessage('DARNEO_OZON_MAIN_HELPER_DATE_MONTH_SHORT_10'),
            Loc::getMessage('DARNEO_OZON_MAIN_HELPER_DATE_MONTH_SHORT_11'),
            Loc::getMessage('DARNEO_OZON_MAIN_HELPER_DATE_MONTH_SHORT_12'),
        ];
    }

    public static function getRussianDayName(Type\DateTime $dateTime): string
    {
        $day = $dateTime->format('j'); // число без ведущего нуля
        $dayOfWeek = $dateTime->format('N'); // день недели (1 - понедельник, ..., 7 - воскресенье)
        $weekDays = [
            Loc::getMessage('DARNEO_OZON_MAIN_HELPER_DATE_DAY_SHORT_1'),
            Loc::getMessage('DARNEO_OZON_MAIN_HELPER_DATE_DAY_SHORT_2'),
            Loc::getMessage('DARNEO_OZON_MAIN_HELPER_DATE_DAY_SHORT_3'),
            Loc::getMessage('DARNEO_OZON_MAIN_HELPER_DATE_DAY_SHORT_4'),
            Loc::getMessage('DARNEO_OZON_MAIN_HELPER_DATE_DAY_SHORT_5'),
            Loc::getMessage('DARNEO_OZON_MAIN_HELPER_DATE_DAY_SHORT_6'),
            Loc::getMessage('DARNEO_OZON_MAIN_HELPER_DATE_DAY_SHORT_7')
        ];

        $formattedDate = $day . ', ' . $weekDays[$dayOfWeek - 1];

        return $formattedDate;
    }
}
