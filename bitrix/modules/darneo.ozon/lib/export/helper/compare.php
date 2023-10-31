<?php

namespace Darneo\Ozon\Export\Helper;

use Bitrix\Main\Localization\Loc;

class Compare
{
    public const EQUAL = 'EQUAL';
    public const NOT_EQUAL = 'NOT_EQUAL';
    public const MORE = 'MORE';
    public const MORE_OR_EQUAL = 'MORE_OR_EQUAL';
    public const LESS = 'LESS';
    public const LESS_OR_EQUAL = 'LESS_OR_EQUAL';
    public const LIKE = 'LIKE';
    public const NOT_LIKE = 'NOT_LIKE';
    public const EMPTY = 'EMPTY';
    public const NOT_EMPTY = 'NOT_EMPTY';

    public static function getString(string $type = ''): array
    {
        $data = [
            [
                'ID' => self::EQUAL,
                'NAME' => self::getName(self::EQUAL),
                'SELECTED' => $type === self::EQUAL
            ],
            [
                'ID' => self::NOT_EQUAL,
                'NAME' => self::getName(self::NOT_EQUAL),
                'SELECTED' => $type === self::NOT_EQUAL
            ],
            [
                'ID' => self::LIKE,
                'NAME' => self::getName(self::LIKE),
                'SELECTED' => $type === self::LIKE
            ],
            [
                'ID' => self::NOT_LIKE,
                'NAME' => self::getName(self::NOT_LIKE),
                'SELECTED' => $type === self::NOT_LIKE
            ],
            [
                'ID' => self::EMPTY,
                'NAME' => self::getName(self::EMPTY),
                'SELECTED' => $type === self::EMPTY
            ],
            [
                'ID' => self::NOT_EMPTY,
                'NAME' => self::getName(self::NOT_EMPTY),
                'SELECTED' => $type === self::NOT_EMPTY
            ],
        ];

        return $data;
    }

    public static function getName(string $type = ''): string
    {
        switch ($type) {
            case self::EQUAL:
                $name = Loc::getMessage('DARNEO_OZON_EXPORT_HELPER_COMPARE_EQUAL');
                break;
            case self::NOT_EQUAL:
                $name = Loc::getMessage('DARNEO_OZON_EXPORT_HELPER_COMPARE_NOT_EQUAL');
                break;
            case self::LIKE:
                $name = Loc::getMessage('DARNEO_OZON_EXPORT_HELPER_COMPARE_LIKE');
                break;
            case self::NOT_LIKE:
                $name = Loc::getMessage('DARNEO_OZON_EXPORT_HELPER_COMPARE_NOT_LIKE');
                break;
            case self::EMPTY:
                $name = Loc::getMessage('DARNEO_OZON_EXPORT_HELPER_COMPARE_EMPTY');
                break;
            case self::NOT_EMPTY:
                $name = Loc::getMessage('DARNEO_OZON_EXPORT_HELPER_COMPARE_NOT_EMPTY');
                break;
            case self::MORE:
                $name = Loc::getMessage('DARNEO_OZON_EXPORT_HELPER_COMPARE_MORE');
                break;
            case self::MORE_OR_EQUAL:
                $name = Loc::getMessage('DARNEO_OZON_EXPORT_HELPER_COMPARE_MORE_OR_EQUAL');
                break;
            case self::LESS:
                $name = Loc::getMessage('DARNEO_OZON_EXPORT_HELPER_COMPARE_LESS');
                break;
            case self::LESS_OR_EQUAL:
                $name = Loc::getMessage('DARNEO_OZON_EXPORT_HELPER_COMPARE_LESS_OR_EQUAL');
                break;
            default:
                $name = '[---]';
                break;
        }

        return $name;
    }

    public static function getNumber(): array
    {
        $data = [
            [
                'ID' => self::EQUAL,
                'NAME' => self::getName(self::EQUAL)
            ],
            [
                'ID' => self::NOT_EQUAL,
                'NAME' => self::getName(self::NOT_EQUAL)
            ],
            [
                'ID' => self::MORE,
                'NAME' => self::getName(self::MORE)
            ],
            [
                'ID' => self::MORE_OR_EQUAL,
                'NAME' => self::getName(self::MORE_OR_EQUAL)
            ],
            [
                'ID' => self::LESS,
                'NAME' => self::getName(self::LESS)
            ],
            [
                'ID' => self::LESS_OR_EQUAL,
                'NAME' => self::getName(self::LESS_OR_EQUAL)
            ],
            [
                'ID' => self::EMPTY,
                'NAME' => self::getName(self::EMPTY)
            ],
            [
                'ID' => self::NOT_EMPTY,
                'NAME' => self::getName(self::NOT_EMPTY)
            ],
        ];

        return $data;
    }

    public static function getEnum(string $type = ''): array
    {
        $data = [
            [
                'ID' => self::EQUAL,
                'NAME' => self::getName(self::EQUAL),
                'SELECTED' => $type === self::EQUAL
            ],
            [
                'ID' => self::NOT_EQUAL,
                'NAME' => self::getName(self::NOT_EQUAL),
                'SELECTED' => $type === self::NOT_EQUAL
            ],
        ];

        return $data;
    }
}
