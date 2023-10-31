<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UserTable;

class OzonUserInfoLeftComponent extends CBitrixComponent
{
    private static array $moduleNames = [];

    public function executeComponent(): array
    {
        $result = [];
        try {
            $this->loadModules();
            $this->setTemplateData();
            $this->includeComponentTemplate();
        } catch (Exception $e) {
            ShowError($e->getMessage());
        }

        return $result;
    }

    private function loadModules(): void
    {
        foreach (self::$moduleNames as $moduleName) {
            $moduleLoaded = Loader::includeModule($moduleName);
            if (!$moduleLoaded) {
                throw new LoaderException(
                    Loc::getMessage('PROMARINE_MODULE_LOAD_ERROR', ['#MODULE_NAME#' => $moduleName])
                );
            }
        }
    }

    private function setTemplateData(): void
    {
        $userId = (new CUser())->GetID();
        $user = $this->getUserName($userId);
        $this->arResult['DATA_VUE'] = $user;
    }

    private function getUserName(int $userId): array
    {
        $user = [];
        $parameters = [
            'filter' => [
                'ID' => $userId
            ],
            'select' => ['ID', 'NAME', 'LAST_NAME', 'EMAIL', 'PERSONAL_PHONE'],
            'cache' => ['ttl' => 86400]
        ];
        $user = UserTable::getList($parameters);
        if ($row = $user->fetch()) {
            $user = $row;
        }

        $name = $user['NAME'];
        $lastName = $user['LAST_NAME'];
        $phone = $user['PERSONAL_PHONE'];

        if ($lastName) {
            $userName = $lastName;
            if ($name) {
                $userName .= ' ' . $name;
            }
        } else {
            $userName = $phone;
        }

        return [
            'ID' => $user['ID'],
            'NAME' => $user['NAME'],
            'LAST_NAME' => $user['LAST_NAME'],
            'EMAIL' => $user['EMAIL'],
            'PERSONAL_PHONE' => $user['PERSONAL_PHONE'],
            'FULL_NAME' => $userName,
        ];
    }

    public function onPrepareComponentParams($arParams): array
    {
        $this->arParams = $arParams;

        return $this->arParams;
    }
}
