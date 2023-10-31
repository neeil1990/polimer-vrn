<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Security\Sign\Signer;
use Darneo\Ozon\Main\Table\ClientKeyTable;

class OzonSettingsKeyListComponent extends CBitrixComponent
{
    private static array $moduleNames = ['darneo.ozon'];

    public function executeComponent(): array
    {
        $result = [];
        try {
            $this->loadModules();
            $this->dataManager();
            switch ($this->arParams['ACTION']) {
                case 'list':
                    $this->setTemplateData();
                    $result = $this->getActionResult(['STATUS' => 'SUCCESS']);
                    break;
                case 'add':
                    $name = $this->request['name'] ?: '';
                    $clientId = $this->request['clientId'];
                    $apiKey = $this->request['apiKey'];
                    $isMain = (bool)$this->request['isMain'];
                    $status = $this->addRow($clientId, $apiKey, $name, $isMain);
                    $this->setTemplateData();
                    $result = $this->getActionResult($status);
                    break;
                case 'update':
                    $name = $this->request['name'] ?: '';
                    $rowId = $this->request['rowId'];
                    $clientId = $this->request['clientId'];
                    $apiKey = $this->request['apiKey'];
                    $isMain = $this->request['isMain'];
                    $status = $this->updateRow($rowId, $clientId, $apiKey, $name, $isMain);
                    $this->setTemplateData();
                    $result = $this->getActionResult($status);
                    break;
                case 'delete':
                    $rowId = $this->request['rowId'];
                    $status = $this->deleteRow($rowId);
                    $this->setTemplateData();
                    $result = $this->getActionResult($status);
                    break;
                default:
                    $this->setTemplateData();
                    $this->includeComponentTemplate();
            }
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

    private function dataManager(): void
    {
    }

    private function setTemplateData(): void
    {
        $rows = $this->getList();
        $this->arResult['DATA_VUE'] = [
            'ITEMS' => $rows
        ];

        $this->arResult['PATH_TO_AJAX'] = $this->getPath() . '/ajax.php';
        $this->arResult['SIGNED_PARAMS'] = (new Signer())->sign(
            base64_encode(serialize($this->arParams)),
            'darneo.ozon_v3.settings.key.list'
        );
    }

    private function getList(): array
    {
        $rows = [];
        $yes = Loc::getMessage('DARNEO_OZON_MODULE_KEY_LIST_YES');
        $no = Loc::getMessage('DARNEO_OZON_MODULE_KEY_LIST_NO');
        $parameters = [
            'select' => ['ID', 'CLIENT_ID', 'KEY', 'NAME', 'DEFAULT'],
            'order' => ['DEFAULT' => 'DESC', 'NAME' => 'ASC']
        ];
        $result = ClientKeyTable::getList($parameters);
        while ($row = $result->fetch()) {
            $row['DEFAULT_HTML'] = $row['DEFAULT'] ? $yes : $no;
            $rows[] = $row;
        }

        return $rows;
    }

    private function getActionResult(array $status): array
    {
        $result = [
            'DATA_VUE' => $this->arResult['DATA_VUE']
        ];

        return array_merge($status, $result);
    }

    private function addRow(int $clientId, string $apiKey, string $name = '', bool $isMain = false): array
    {
        $params = ['CLIENT_ID' => $clientId, 'KEY' => $apiKey, 'DEFAULT' => $isMain];
        if ($name) {
            $params['NAME'] = $name;
        }
        $result = ClientKeyTable::add($params);
        if ($result->isSuccess()) {
            return ['STATUS' => 'SUCCESS'];
        }

        return ['STATUS' => 'ERROR', 'ERROR_LIST' => $result->getErrorMessages()];
    }

    private function updateRow(
        int $rowId,
        int $clientId,
        string $apiKey,
        string $name = '',
        bool $isMain = false
    ): array {
        $params = ['CLIENT_ID' => $clientId, 'KEY' => $apiKey, 'NAME' => $name, 'DEFAULT' => $isMain];
        $result = ClientKeyTable::update($rowId, $params);
        if ($result->isSuccess()) {
            return ['STATUS' => 'SUCCESS'];
        }

        return ['STATUS' => 'ERROR', 'ERROR_LIST' => $result->getErrorMessages()];
    }

    private function deleteRow(int $rowId): array
    {
        $result = ClientKeyTable::delete($rowId);
        if ($result->isSuccess()) {
            return ['STATUS' => 'SUCCESS'];
        }

        return ['STATUS' => 'ERROR', 'ERROR_LIST' => $result->getErrorMessages()];
    }

    public function onPrepareComponentParams($arParams): array
    {
        $this->arParams = $arParams;

        return $this->arParams;
    }
}
