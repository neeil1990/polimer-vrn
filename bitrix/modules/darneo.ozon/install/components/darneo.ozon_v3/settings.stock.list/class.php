<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Application;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\Security\Sign\Signer;
use Darneo\Ozon\Import\Table\StockTable;

class OzonSettingsStockListComponent extends CBitrixComponent
{
    private static array $moduleNames = ['darneo.ozon'];

    public function executeComponent(): array
    {
        $result = [];
        try {
            $this->loadModules();
            $this->dataManager();
            switch ($this->arParams['ACTION']) {
                case 'import':
                    $this->deleteData();
                    $status = $this->importStock();
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

    private function deleteData(): void
    {
        $connection = Application::getConnection();
        $entitiesDataClasses = [
            StockTable::class
        ];
        /** @var DataManager $entityDataClass */
        foreach ($entitiesDataClasses as $entityDataClass) {
            if ($connection->isTableExists($entityDataClass::getTableName())) {
                $connection->dropTable($entityDataClass::getTableName());
            }
            $entityDataClass::getEntity()->createDbTable();
        }
    }

    private function importStock(): array
    {
        $errors = [];

        $data = (new \Darneo\Ozon\Api\v2\Warehouse())->list();
        if (!$data['result']) {
            $errors[] = Loc::getMessage('DARNEO_OZON_MODULE_STOCK_LIST_EMPTY_DATA');
        }
        foreach ($data['result'] as $datum) {
            $result = StockTable::add(
                [
                    'ID' => $datum['warehouse_id'],
                    'NAME' => $datum['name'],
                    'IS_RFBS' => $datum['is_rfbs']
                ]
            );
            if (!$result->isSuccess()) {
                $errors[] = implode(',', $result->getErrorMessages());
            }
        }

        if ($errors) {
            return ['STATUS' => 'ERROR', 'ERROR_LIST' => $errors];
        }

        return ['STATUS' => 'SUCCESS'];
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
            'darneo.ozon.settings.stock.list'
        );
    }

    private function getList(): array
    {
        $rows = [];
        $yes = Loc::getMessage('DARNEO_OZON_MODULE_STOCK_LIST_YES');
        $no = Loc::getMessage('DARNEO_OZON_MODULE_STOCK_LIST_NO');
        $parameters = [
            'select' => ['ID', 'NAME', 'IS_RFBS'],
            'order' => ['ID' => 'DESC']
        ];
        $result = StockTable::getList($parameters);
        while ($row = $result->fetch()) {
            $row['IS_RFBS'] = $row['IS_RFBS'] ? $yes : $no;
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

    public function onPrepareComponentParams($arParams): array
    {
        $this->arParams = $arParams;

        return $this->arParams;
    }
}
