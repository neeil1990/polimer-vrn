<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Security\Sign\Signer;
use Darneo\Ozon\Import\Table\ProductListTable;

class OzonDataCatalogListComponent extends CBitrixComponent
{
    private static array $moduleNames = ['darneo.ozon'];
    protected int $limit = 50;
    protected int $page = 1;
    protected int $totalCount = 0;
    protected string $filterSearch;
    protected string $filterStatus;

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
        $this->filterSearch = $this->request['filterSearch'] ?: '';
        $this->page = (int)$this->request['page'] > 0 ? (int)$this->request['page'] : 1;
        $this->filterStatus = $this->request['filter'] ?: 'all';
    }

    private function setTemplateData(): void
    {
        $elements = $this->getList();
        $isPageStop = $this->page * $this->limit >= $this->totalCount;

        $this->arResult['DATA_VUE'] = [
            'LIST' => $elements,
            'PAGE' => $this->page,
            'FINAL_PAGE' => $isPageStop,
            'FILTER' => [
                'LIST' => $this->getFilterList(),
                'SEARCH' => $this->filterSearch
            ]
        ];

        $this->arResult['PATH_TO_AJAX'] = $this->getPath() . '/ajax.php';
        $this->arResult['SIGNED_PARAMS'] = (new Signer())->sign(
            base64_encode(serialize($this->arParams)),
            'darneo.ozon.data.catalog.list'
        );
    }

    private function getList(): array
    {
        $rows = [];
        $parameters = [
            'select' => [
                'ID',
                'OFFER_ID',
                'NAME',
                'STATUS_NAME',
                'CATEGORY_ID',
                'IS_ERROR',
                'STOCK_FBS',
                'STOCK_FBS_RESERVED',
                'STOCK_FBO',
                'STOCK_FBO_RESERVED',
                'CATEGORY_NAME' => 'CATEGORY.TITLE',
                'JSON'
            ],
            'order' => ['ID' => 'DESC'],
            'limit' => $this->page * $this->limit
        ];
        if ($this->filterSearch) {
            $parameters['filter']['OFFER_ID'] = $this->filterSearch;
        }
        switch ($this->filterStatus) {
            case 'validation_state_fail':
                $parameters['filter']['IS_ERROR'] = true;
                break;
        }

        $result = ProductListTable::getList($parameters);
        while ($row = $result->fetch()) {
            $row['PRICE_FORMATED'] = number_format($row['PRICE'], 2, '.', ' ');
            $row['PRICE_OLD_FORMATED'] = number_format($row['PRICE_OLD'], 2, '.', ' ');
            $rows[] = $row;
        }

        $this->initElementCountAll($parameters);

        return $rows;
    }

    private function initElementCountAll(array $parameters): void
    {
        unset($parameters['limit']);
        $result = ProductListTable::getList($parameters);
        $this->totalCount = $result->getSelectedRowsCount();
    }

    private function getFilterList(): array
    {
        return [
            [
                'CODE' => 'all',
                'NAME' => Loc::getMessage('DARNEO_OZON_MODULE_CATALOG_LIST_FILTER_ALL'),
                'ACTIVE' => $this->filterStatus === 'all',
                'COUNT' => $this->getElementCountFilter()
            ],
            [
                'CODE' => 'validation_state_fail',
                'NAME' => Loc::getMessage('DARNEO_OZON_MODULE_CATALOG_LIST_FILTER_FAIL'),
                'ACTIVE' => $this->filterStatus === 'validation_state_fail',
                'COUNT' => $this->getElementCountFilter(['IS_ERROR' => true])
            ],
        ];
    }

    private function getElementCountFilter(array $filter = []): int
    {
        $parameters = [
            'select' => ['ID'],
            'filter' => $filter
        ];

        return ProductListTable::getList($parameters)->getSelectedRowsCount();
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
