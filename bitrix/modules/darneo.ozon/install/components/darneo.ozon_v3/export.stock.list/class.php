<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Catalog\CatalogIblockTable;
use Bitrix\Catalog\StoreTable;
use Bitrix\Iblock\IblockTable;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Security\Sign\Signer;
use Darneo\Ozon\Export\Table\StockListTable;

class OzonExportStockListComponent extends CBitrixComponent
{
    private static array $moduleNames = ['darneo.ozon'];
    private array $store;

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
                    $iblockId = $this->request['iblockId'];
                    $status = $this->addRow($iblockId, $name);
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
        $this->store = $this->getStore();
    }

    private function getStore(): array
    {
        $rows = [];
        $parameters = [
            'select' => ['ID', 'TITLE'],
            'order' => ['ID' => 'ASC'],
            'cache' => ['ttl' => 86400]
        ];
        $result = StoreTable::getList($parameters);
        while ($row = $result->fetch()) {
            $rows[$row['ID']] = $row['TITLE'];
        }

        return $rows;
    }

    private function getList(): array
    {
        $rows = [];
        $parameters = [
            'select' => [
                'ID',
                'TITLE',
                'IBLOCK_ID',
                'OZON_STOCK_NAME' => 'OZON_STOCK.NAME',
                'STORE_ID',
                'MIN_COUNT_STORE',
                'MAX_COUNT_STORE',
                'IS_CRON',
            ],
            'order' => ['TITLE' => 'ASC']
        ];
        $result = StockListTable::getList($parameters);
        while ($row = $result->fetch()) {
            $row['DETAIL_PAGE_URL'] = $this->generateDetailUrl($row['ID']);

            $row['MIN_COUNT_STORE'] = $row['MIN_COUNT_STORE'] ?: '';
            $row['MAX_COUNT_STORE'] = $row['MAX_COUNT_STORE'] ?: '';

            $storeName = [];
            foreach ($row['STORE_ID'] as $storeId) {
                $storeName[] = $this->store[$storeId];
            }
            $row['STORE_SHOP'] = implode('<br>', $storeName);

            $rows[] = $row;
        }

        return $rows;
    }

    private function generateDetailUrl(int $elementId): string
    {
        return $this->arParams['SEF_FOLDER'] . str_replace(
                '#ELEMENT_ID#',
                $elementId,
                $this->arParams['URL_TEMPLATES']['detail']
            );
    }

    private function setTemplateData(): void
    {
        $iblock = $this->getIblockCatalog();
        $rows = $this->getList();

        foreach ($rows as $key => $row) {
            $rows[$key]['IBLOCK_NAME'] = $iblock[$row['IBLOCK_ID']]['NAME'] ?: '';
        }

        $iblock = array_values($iblock);

        $this->arResult['DATA_VUE'] = [
            'ITEMS' => $rows,
            'IBLOCK' => $iblock
        ];

        $this->arResult['PATH_TO_AJAX'] = $this->getPath() . '/ajax.php';
        $this->arResult['SIGNED_PARAMS'] = (new Signer())->sign(
            base64_encode(serialize($this->arParams)),
            'darneo.ozon.export.stock.list'
        );
    }

    private function getIblockCatalog(): array
    {
        $rows = [];
        $parameters = [
            'filter' => [
                'ID' => $this->getCatalogIblockIds(),
                'ACTIVE' => 'Y'
            ],
            'select' => ['ID', 'NAME'],
            'order' => ['ID' => 'ASC']
        ];
        $result = IblockTable::getList($parameters);
        while ($row = $result->fetch()) {
            $rows[$row['ID']] = $row;
        }

        return $rows;
    }

    private function getCatalogIblockIds(): array
    {
        $rows = [];
        $parameters = [
            'filter' => [
                'PRODUCT_IBLOCK_ID' => false
            ],
            'select' => ['IBLOCK_ID'],
        ];
        $result = CatalogIblockTable::getList($parameters);
        while ($row = $result->fetch()) {
            $rows[] = $row['IBLOCK_ID'];
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

    private function addRow(int $iblockId, string $name = ''): array
    {
        $params = ['IBLOCK_ID' => $iblockId];
        if ($name) {
            $params['TITLE'] = $name;
        }
        $result = StockListTable::add($params);
        if ($result->isSuccess()) {
            return ['STATUS' => 'SUCCESS', 'REDIRECT' => $this->generateDetailUrl($result->getId())];
        }

        return ['STATUS' => 'ERROR', 'ERROR_LIST' => $result->getErrorMessages()];
    }

    private function deleteRow(int $rowId): array
    {
        $result = StockListTable::delete($rowId);
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
