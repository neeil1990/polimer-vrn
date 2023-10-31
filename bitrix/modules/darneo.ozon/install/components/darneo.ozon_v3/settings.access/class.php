<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\GroupTable;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Security\Sign\Signer;
use Darneo\Ozon\Main\Table\AccessTable;

class OzonSettingsAccessComponent extends CBitrixComponent
{
    private static array $moduleNames = ['darneo.ozon'];
    private array $groupSelected = [1, 2, 3, 4];

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
                    $rowId = $this->request['rowId'] ?: '';
                    $status = $this->addRow($rowId);
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
        $group = $this->getGroupList();

        $this->arResult['DATA_VUE'] = [
            'ITEMS' => $rows,
            'GROUP' => $group
        ];

        $this->arResult['PATH_TO_AJAX'] = $this->getPath() . '/ajax.php';
        $this->arResult['SIGNED_PARAMS'] = (new Signer())->sign(
            base64_encode(serialize($this->arParams)),
            'darneo.ozon.settings.access'
        );
    }

    private function getList(): array
    {
        $rows = [];

        $parameters = [
            'select' => [
                'GROUP_ID',
                'NAME' => 'GROUP.NAME'
            ],
            'order' => ['GROUP_ID' => 'ASC'],
        ];
        $result = AccessTable::getList($parameters);
        while ($row = $result->fetch()) {
            $this->groupSelected[] = $row['GROUP_ID'];
            $rows[] = [
                'ID' => $row['GROUP_ID'],
                'NAME' => $row['NAME']
            ];
        }

        return $rows;
    }

    private function getGroupList(): array
    {
        $rows = [];

        $parameters = [
            'filter' => ['!=ID' => $this->groupSelected],
            'select' => ['ID', 'NAME'],
        ];
        $result = GroupTable::getList($parameters);
        while ($row = $result->fetch()) {
            $rows[] = [
                'ID' => $row['ID'],
                'NAME' => $row['NAME']
            ];
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

    private function addRow(int $rowId): array
    {
        $params = ['GROUP_ID' => $rowId];
        $result = AccessTable::add($params);
        if ($result->isSuccess()) {
            return ['STATUS' => 'SUCCESS'];
        }

        return ['STATUS' => 'ERROR', 'ERROR_LIST' => $result->getErrorMessages()];
    }

    private function deleteRow(int $rowId): array
    {
        $result = AccessTable::delete($rowId);
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