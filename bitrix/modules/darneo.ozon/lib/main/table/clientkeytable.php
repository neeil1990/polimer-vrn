<?php

namespace Darneo\Ozon\Main\Table;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM;
use Bitrix\Main\ORM\Data;
use Bitrix\Main\ORM\Fields;
use Darneo\Ozon\EventHandlers;

class ClientKeyTable extends Data\DataManager
{
    public static function getTableName(): string
    {
        return 'darneo_ozon_main_settings_client_key';
    }

    public static function getMap(): array
    {
        return [
            new Fields\IntegerField('ID', [
                'primary' => true,
                'autocomplete' => true,
                'title' => Loc::getMessage('DARNEO_OZON_MAIN_TABLE_CLIENT_KEY_ID')
            ]),
            new Fields\StringField('NAME', [
                'required' => true,
                'default_value' => Loc::getMessage('DARNEO_OZON_MAIN_TABLE_CLIENT_KEY_NAME_DEFAULT'),
                'title' => Loc::getMessage('DARNEO_OZON_MAIN_TABLE_CLIENT_KEY_NAME')
            ]),
            new Fields\IntegerField('CLIENT_ID', [
                'required' => true,
                'title' => 'CLIENT ID'
            ]),
            new Fields\StringField('KEY', [
                'required' => true,
                'title' => 'API KEY'
            ]),
            new Fields\BooleanField('DEFAULT', [
                'required' => false,
                'title' => Loc::getMessage('DARNEO_OZON_MAIN_TABLE_CLIENT_DEFAULT')
            ]),
        ];
    }

    public static function onAfterAdd(ORM\Event $event): ORM\EventResult
    {
        return EventHandlers\Orm\Handlers::setDefaultValue($event);
    }

    public static function onAfterUpdate(ORM\Event $event): ORM\EventResult
    {
        return EventHandlers\Orm\Handlers::setDefaultValue($event);
    }

    public static function OnAfterDelete(ORM\Event $event): void
    {
        global $DB;

        $id = $event->getParameter('primary')['ID'];

        $tables = self::getTableSettingAll($id);
        foreach ($tables as $table) {
            $errMess = '';
            $strSql = 'DROP TABLE ' . $table;
            $DB->Query($strSql, false, $errMess . __LINE__);
        }
    }

    public static function getTableSettingAll(int $settingId): array
    {
        $rows = [];

        global $DB;
        $strSql = "SHOW TABLES LIKE 'darneo_ozon_%_" . $settingId . "'";
        $errMess = '';
        $res = $DB->Query($strSql, false, $errMess . __LINE__);
        while ($row = $res->Fetch()) {
            foreach ($row as $tableName) {
                $rows[] = $tableName;
            }
        }

        return $rows;
    }

    public static function getDefaultRow(): array
    {
        $rows = [];
        $parameters = [
            'filter' => ['DEFAULT' => true],
            'select' => ['ID', 'CLIENT_ID', 'KEY', 'NAME', 'DEFAULT'],
            'order' => ['ID' => 'DESC'],
            'limit' => 1,
            'cache' => ['ttl' => 86400]
        ];
        $result = self::getList($parameters);
        if ($row = $result->fetch()) {
            $rows = $row;
        }

        return $rows;
    }

    public static function getLastRow(): array
    {
        $rows = [];
        $parameters = [
            'select' => ['ID', 'CLIENT_ID', 'KEY', 'NAME', 'DEFAULT'],
            'order' => ['ID' => 'DESC'],
            'limit' => 1,
            'cache' => ['ttl' => 86400]
        ];
        $result = self::getList($parameters);
        if ($row = $result->fetch()) {
            $rows = $row;
        }

        return $rows;
    }
}
