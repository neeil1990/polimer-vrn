<?php

namespace Darneo\Ozon\Main\Table;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Data;
use Bitrix\Main\ORM\Fields;

class SettingsTable extends Data\DataManager
{
    public static function getTableName(): string
    {
        return 'darneo_ozon_settings';
    }

    public static function getMap(): array
    {
        return [
            new Fields\StringField('CODE', [
                'primary' => true,
                'required' => true,
                'title' => Loc::getMessage('DARNEO_OZON_MAIN_TABLE_SETTINGS_CODE')
            ]),
            new Fields\StringField('TITLE', [
                'required' => true,
                'title' => Loc::getMessage('DARNEO_OZON_MAIN_TABLE_SETTINGS_TITLE')
            ]),
            new Fields\TextField('VALUE', [
                'serialized' => true,
                'title' => Loc::getMessage('DARNEO_OZON_MAIN_TABLE_SETTINGS_VALUE')
            ]),
        ];
    }
}
