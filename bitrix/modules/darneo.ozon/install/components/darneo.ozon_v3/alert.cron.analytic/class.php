<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\Localization\Loc;
use Darneo\Ozon\Main\Table\SettingsCronTable;

class OzonAlertCronAnalyticComponent extends CBitrixComponent
{
    private static array $moduleNames = ['darneo.ozon'];

    private array $category = [];

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
                    Loc::getMessage('DARNEO_OZON_MODULE_LOAD_ERROR', ['#MODULE_NAME#' => $moduleName])
                );
            }
        }
    }

    private function setTemplateData(): void
    {
        $settings = $this->getGeneralActive();

        $this->arResult = [
            'IS_ACTIVE' => $settings['VALUE'] && $settings['DATE_START'],
            'SETTING_CRON_FOLDER' => $this->arParams['SETTING_CRON_FOLDER'],
        ];
    }

    private function getGeneralActive(): array
    {
        $settings = SettingsCronTable::getById('IMPORT_ANALYTIC')->fetch();

        return $settings;
    }

    public function onPrepareComponentParams($arParams): array
    {
        $this->arParams = $arParams;

        return $this->arParams;
    }
}
