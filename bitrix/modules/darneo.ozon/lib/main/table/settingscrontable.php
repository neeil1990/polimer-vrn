<?php

namespace Darneo\Ozon\Main\Table;

use Bitrix\Main\Application;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Data;
use Bitrix\Main\ORM\Fields;
use Darneo\Ozon\EventHandlers;
use Darneo\Ozon\Install\SettingsCron;

class SettingsCronTable extends Data\DataManager
{
    public static string $tablePrefix = '';

    public static function getTableName(): string
    {
        if (self::$tablePrefix) {
            return 'darneo_ozon_settings_cron_' . self::$tablePrefix;
        }
        return 'darneo_ozon_settings_cron';
    }

    public static function setTablePrefix(string $tablePrefix): void
    {
        self::$tablePrefix = $tablePrefix;
        if ($tablePrefix) {
            $connection = Application::getConnection();
            $tableName = 'darneo_ozon_settings_cron_' . self::$tablePrefix;
            if (!$connection->isTableExists($tableName)) {
                self::getEntity()->createDbTable();
                (new SettingsCron())->setValue();
            }
        }
    }

    public static function getMap(): array
    {
        return [
            new Fields\StringField('CODE', [
                'primary' => true,
                'required' => true,
                'title' => Loc::getMessage('DARNEO_OZON_MAIN_TABLE_SETTINGS_CRON_CODE')
            ]),
            new Fields\IntegerField('SORT', [
                'required' => true,
            ]),
            new Fields\BooleanField('IS_STARTED', [
                'title' => Loc::getMessage('DARNEO_OZON_MAIN_TABLE_SETTINGS_CRON_IS_STARTED')
            ]),
            new Fields\DatetimeField('DATE_START', [
                'title' => Loc::getMessage('DARNEO_OZON_MAIN_TABLE_SETTINGS_CRON_DATE_START')
            ]),
            new Fields\DatetimeField('DATE_FINISH', [
                'title' => Loc::getMessage('DARNEO_OZON_MAIN_TABLE_SETTINGS_CRON_DATE_FINISH')
            ]),
            new Fields\BooleanField('VALUE', [
                'default_value' => true,
                'title' => Loc::getMessage('DARNEO_OZON_MAIN_TABLE_SETTINGS_CRON_VALUE')
            ]),
        ];
    }
}
