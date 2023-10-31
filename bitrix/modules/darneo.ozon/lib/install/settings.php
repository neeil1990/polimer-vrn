<?php

namespace Darneo\Ozon\Install;

use Bitrix\Main\Localization\Loc;
use Darneo\Ozon\Main\Table\SettingsTable;

class Settings
{
    private array $defaultValue;

    public function __construct()
    {
        $this->defaultValue = [
            [
                'TITLE' => Loc::getMessage('DARNEO_OZON_INSTALL_SETTINGS_IS_TEST'),
                'CODE' => 'IS_TEST',
            ],
            [
                'TITLE' => Loc::getMessage('DARNEO_OZON_INSTALL_SETTINGS_IS_CHAT'),
                'CODE' => 'IS_CHAT',
                'VALUE' => 1,
            ],
        ];
    }

    public function setValue(): void
    {
        if (SettingsTable::getCount() === 0) {
            foreach ($this->defaultValue as $value) {
                SettingsTable::add($value);
            }
        }
    }
}
