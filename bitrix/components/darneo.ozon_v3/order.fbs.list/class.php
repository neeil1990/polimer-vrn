<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Security\Sign\Signer;
use Bitrix\Main\Type;
use Darneo\Ozon\Order\Helper\Fbs as HelperFbs;
use Darneo\Ozon\Order\Table\FbsListTable;

class OzonOrderFbsListComponent extends CBitrixComponent
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
            'darneo.ozon.order.fbs.list'
        );
    }

    private function getList(): array
    {
        $rows = [];
        $parameters = [
            'select' => [
                'ID',
                'POSTING_NUMBER',
                'STATUS',
                'PRODUCTS',
                'DELIVERY_METHOD',
                'WAREHOUSE_ID',
                'TPL_INTEGRATION_TYPE',
                'IN_PROCESS_AT',
                'SHIPMENT_DATE',
                'DELIVERY_DATE',
                'CANCELLATION',
                'FINANCIAL_DATA',
            ],
            'order' => ['IN_PROCESS_AT' => 'DESC'],
            'limit' => $this->page * $this->limit
        ];
        if ($this->filterSearch) {
            // $parameters['filter']['OFFER_ID'] = $this->filterSearch;
        }
        switch ($this->filterStatus) {
        }

        $result = FbsListTable::getList($parameters);
        while ($row = $result->fetch()) {
            if ($row['IN_PROCESS_AT'] instanceof Type\DateTime) {
                $row['IN_PROCESS_AT'] = $row['IN_PROCESS_AT']->format('d.m.Y H:i');
            }
            if ($row['SHIPMENT_DATE'] instanceof Type\DateTime) {
                $row['SHIPMENT_DATE'] = $row['SHIPMENT_DATE']->format('d.m.Y H:i');
            }
            if ($row['DELIVERY_DATE'] instanceof Type\DateTime) {
                $row['DELIVERY_DATE'] = $row['DELIVERY_DATE']->format('d.m.Y H:i');
            }
            $row['STATUS_NAME'] = HelperFbs::getStatusLoc($row['STATUS']);

            $row['IS_NEW'] = HelperFbs::isStatusNew($row['STATUS']);
            $row['IS_ERROR'] = HelperFbs::isStatusError($row['STATUS']);
            $row['IS_FINISH'] = HelperFbs::isStatusFinish($row['STATUS']);

            $sum = 0;
            foreach ($row['FINANCIAL_DATA']['products'] as $product) {
                $sum += $product['price'];
            }
            $sumFormated = number_format($sum, 0, '.', ' ');
            $row['SUM'] = $sum;
            $row['SUM_FORMATED'] = $sumFormated;

            $rows[] = $row;
        }

        $this->initElementCountAll($parameters);

        return $rows;
    }

    private function initElementCountAll(array $parameters): void
    {
        unset($parameters['limit']);
        $result = FbsListTable::getList($parameters);
        $this->totalCount = $result->getSelectedRowsCount();
    }

    private function getFilterList(): array
    {
        return [
            /*[
                'CODE' => 'all',
                'NAME' => Loc::getMessage('DARNEO_OZON_MODULE_ORDER_LIST_FILTER_ALL'),
                'ACTIVE' => $this->filterStatus === 'all',
                'COUNT' => $this->getElementCountFilter()
            ],
            [
                'CODE' => 'validation_state_fail',
                'NAME' => Loc::getMessage('DARNEO_OZON_MODULE_ORDER_LIST_FILTER_FAIL'),
                'ACTIVE' => $this->filterStatus === 'validation_state_fail',
                'COUNT' => $this->getElementCountFilter(['IS_ERROR' => true])
            ],*/
        ];
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

    private function getElementCountFilter(array $filter = []): int
    {
        $parameters = [
            'select' => ['ID'],
            'filter' => $filter
        ];

        return FbsListTable::getList($parameters)->getSelectedRowsCount();
    }
}
