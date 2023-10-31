<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Security\Sign\Signer;
use Bitrix\Main\Type;
use Darneo\Ozon\Order\Helper\Fbo as HelperFbo;
use Darneo\Ozon\Order\Table\FboListTable;

class OzonOrderFboListComponent extends CBitrixComponent
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
            'darneo.ozon.order.fbo.list'
        );
    }

    private function getList(): array
    {
        $rows = [];
        $parameters = [
            'select' => [
                'ID',
                'DATE_CREATED',
                'STATUS',
                'POSTING_NUMBER',
                'PRODUCTS',
                'FINANCIAL',
            ],
            'order' => ['DATE_CREATED' => 'DESC'],
            'limit' => $this->page * $this->limit
        ];
        if ($this->filterSearch) {
            // $parameters['filter']['OFFER_ID'] = $this->filterSearch;
        }
        switch ($this->filterStatus) {
        }

        $result = FboListTable::getList($parameters);
        while ($row = $result->fetch()) {
            if ($row['DATE_CREATED'] instanceof Type\DateTime) {
                $row['DATE_CREATED'] = $row['DATE_CREATED']->format('d.m.Y H:i');
            }

            $row['STATUS_NAME'] = HelperFbo::getStatusLoc($row['STATUS']);

            $row['IS_NEW'] = HelperFbo::isStatusNew($row['STATUS']);
            $row['IS_ERROR'] = HelperFbo::isStatusError($row['STATUS']);
            $row['IS_FINISH'] = HelperFbo::isStatusFinish($row['STATUS']);

            $sum = 0;
            foreach ($row['FINANCIAL']['products'] as $product) {
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
        $result = FboListTable::getList($parameters);
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
