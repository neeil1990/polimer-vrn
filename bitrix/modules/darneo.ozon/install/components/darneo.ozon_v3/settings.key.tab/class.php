<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Application;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Uri;
use Darneo\Ozon\Main\Helper\Settings as HelperSettings;
use Darneo\Ozon\Main\Table\ClientKeyTable;

class OzonSettingsKeyTabComponent extends CBitrixComponent
{
    private static array $moduleNames = ['darneo.ozon'];
    private int $keyId = 0;

    public function executeComponent(): int
    {
        try {
            $this->loadModules();
            $this->initSetKey();
            $this->setTemplateData();
            $this->includeComponentTemplate();
        } catch (Exception $e) {
            ShowError($e->getMessage());
        }
        HelperSettings::setKeyIdCurrent($this->keyId);
        return $this->keyId;
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

    private function initSetKey(): void
    {
        $this->keyId = HelperSettings::getKeyIdCurrent();
    }

    private function setTemplateData(): void
    {
        $rows = $this->getList();
        $this->arResult['DATA_VUE'] = [
            'ITEMS' => $rows
        ];
    }

    private function getList(): array
    {
        $rows = [];
        $parameters = [
            'select' => ['ID', 'NAME'],
            'order' => ['DEFAULT' => 'DESC', 'ID' => 'ASC'],
            'cache' => ['ttl' => 86400]
        ];
        $result = ClientKeyTable::getList($parameters);
        while ($row = $result->fetch()) {
            $row['LINK'] = $this->getUrl($row['ID']);
            $row['ACTIVE'] = $this->keyId === (int)$row['ID'];
            $rows[] = $row;
        }

        return $rows;
    }

    private function getUrl(int $keyId): string
    {
        $request = Application::getInstance()->getContext()->getRequest();
        $uriString = $request->getRequestUri();
        $uri = new Uri($uriString);
        $uri->addParams(['key' => $keyId]);

        return $uri->getUri();
    }

    public function onPrepareComponentParams($arParams): array
    {
        $this->arParams = $arParams;

        return $this->arParams;
    }
}
