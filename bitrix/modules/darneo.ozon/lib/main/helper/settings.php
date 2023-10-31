<?php

namespace Darneo\Ozon\Main\Helper;

use Bitrix\Main\Application;
use Darneo\Ozon\Main;
use Darneo\Ozon\Main\Table\SettingsTable;

class Settings
{
    public static function isTest(): bool
    {
        $result = SettingsTable::getById('IS_TEST');
        if ($row = $result->fetch()) {
            return (bool)$row['VALUE'];
        }

        return false;
    }

    public static function isChat(): bool
    {
        $result = SettingsTable::getById('IS_CHAT');
        if ($row = $result->fetch()) {
            return (bool)$row['VALUE'];
        }

        return false;
    }

    public static function setKeyIdCurrent(int $keyId = 0): void
    {
        $_SESSION['DARNEO_OZON_KEY_ID'] = $keyId;
    }

    public static function getKeyIdCurrent(): int
    {
        if ($keyId = (int)$_SESSION['DARNEO_OZON_KEY_ID'] ?: 0) {
            return $keyId;
        }

        $connection = Application::getConnection();
        $tableName = Main\Table\ClientKeyTable::getTableName();
        if ($connection->isTableExists($tableName)) {
            if ($keyId = Main\Table\ClientKeyTable::getDefaultRow()['ID']) {
                return $keyId;
            }
            if ($keyId = Main\Table\ClientKeyTable::getLastRow()['ID']) {
                return $keyId;
            }
        }

        return 0;
    }
}
