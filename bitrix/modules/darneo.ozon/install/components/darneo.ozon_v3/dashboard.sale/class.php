<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Security\Sign\Signer;
use Bitrix\Main\Type;
use Bitrix\Sale\Order;
use Darneo\Ozon\Analytics\Table\SaleTable;
use Darneo\Ozon\Main\Helper\Date as HelperDate;

class OzonDashBoardSaleComponent extends CBitrixComponent
{
    private static array $moduleNames = ['darneo.ozon'];
    private float $sumShop = 0;
    private float $sumOzon = 0;
    private bool $isFilterYear = false;
    private int $filterYear;
    private string $filterPeriod;
    private Type\DateTime $filterStartDate;
    private Type\DateTime $filterEndDate;

    private array $category = [];

    public function executeComponent(): array
    {
        $result = [];
        try {
            $this->loadModules();
            $this->dataManager();

            $this->filterYear = $this->request['year'] ?: date('Y');

            if ($this->request['period']) {
                $this->filterPeriod = $this->request['period'] ?: '7';
                $_SESSION['OZON_DASHBOARD_PERIOD'] = $this->filterPeriod;
            } else {
                $this->filterPeriod = $_SESSION['OZON_DASHBOARD_PERIOD'] ?: '7';
            }

            $filters = $this->getFilter();
            foreach ($filters as $filter) {
                if ($filter['VALUE'] === $this->filterPeriod) {
                    $this->filterStartDate = $filter['START_DATE'];
                    $this->filterEndDate = $filter['END_DATE'];
                    $this->isFilterYear = $filter['VALUE'] === '365';
                }
            }

            switch ($this->arParams['ACTION']) {
                case 'list':
                case 'filter':
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
    }

    private function getFilter(): array
    {
        return [
            [
                'VALUE' => '7',
                'TITLE' => Loc::getMessage('DARNEO_OZON_DASHBOARD_SALE_FILTER_7'),
                'START_DATE' => (new Type\DateTime(date('Y-m-d') . ' 00:00:00', 'Y-m-d H:i:s'))->add('-7 days'),
                'END_DATE' => new Type\DateTime(date('Y-m-d') . ' 23:59:59', 'Y-m-d H:i:s'),
                'ACTIVE' => $this->filterPeriod === '7'
            ],
            [
                'VALUE' => '14',
                'TITLE' => Loc::getMessage('DARNEO_OZON_DASHBOARD_SALE_FILTER_14'),
                'START_DATE' => (new Type\DateTime(date('Y-m-d') . ' 00:00:00', 'Y-m-d H:i:s'))->add('-14 days'),
                'END_DATE' => new Type\DateTime(date('Y-m-d') . ' 23:59:59', 'Y-m-d H:i:s'),
                'ACTIVE' => $this->filterPeriod === '14'
            ],
            [
                'VALUE' => '28',
                'TITLE' => Loc::getMessage('DARNEO_OZON_DASHBOARD_SALE_FILTER_28'),
                'START_DATE' => (new Type\DateTime(date('Y-m-d') . ' 00:00:00', 'Y-m-d H:i:s'))->add('-28 days'),
                'END_DATE' => new Type\DateTime(date('Y-m-d') . ' 23:59:59', 'Y-m-d H:i:s'),
                'ACTIVE' => $this->filterPeriod === '28'
            ],
            [
                'VALUE' => '365',
                'TITLE' => Loc::getMessage('DARNEO_OZON_DASHBOARD_SALE_FILTER_YEAR'),
                'START_DATE' => new Type\DateTime($this->filterYear . '-01-01 00:00:00', 'Y-m-d H:i:s'),
                'END_DATE' => new Type\DateTime($this->filterYear . '-12-31 23:59:59', 'Y-m-d H:i:s'),
                'ACTIVE' => $this->filterPeriod === '365'
            ],
        ];
    }

    private function setTemplateData(): void
    {
        $orderSite = $this->getOrderSite();
        $orderOzon = $this->getOrderOzon();

        $category = $this->isFilterYear ? HelperDate::getRussianMonthName(true) : $this->category;

        $filterVue = [];
        $filterTitle = '';
        $filters = $this->getFilter();
        foreach ($filters as $filter) {
            if ($filter['ACTIVE']) {
                $filterTitle = $filter['TITLE'];
            }
            $filterVue[] = [
                'TITLE' => $filter['TITLE'],
                'VALUE' => $filter['VALUE'],
                'ACTIVE' => $filter['ACTIVE']
            ];
        }

        $this->arResult['DATA_VUE'] = [
            'FILTER' => $filterVue,
            'IS_FILTER_YEAR' => $this->isFilterYear,
            'YEAR' => $this->isFilterYear ? $this->filterYear : $filterTitle,
            'CATEGORY' => array_values($category),
            'SUM' => $this->sumShop + $this->sumOzon,
            'SUM_SHOP' => $this->sumShop,
            'SUM_OZON' => $this->sumOzon,

            'ORDER_SITE' => $orderSite,
            'ORDER_OZON' => $orderOzon,
        ];

        $this->arResult['PATH_TO_AJAX'] = $this->getPath() . '/ajax.php';
        $this->arResult['SIGNED_PARAMS'] = (new Signer())->sign(
            base64_encode(serialize($this->arParams)),
            'darneo.ozon_v3.dashboard.sale'
        );
    }

    private function getOrderSite(): array
    {
        $rows = [];

        if ($this->isFilterYear) {
            for ($i = 1; $i <= 12; $i++) {
                $rows[$i] = 0;
            }
        } else {
            $rows = $this->getDatesBackward();
        }

        $parameters = [
            'filter' => [
                '>=DATE_INSERT' => $this->filterStartDate,
                '<=DATE_INSERT' => $this->filterEndDate,
                'PAYED' => 'Y'
            ],
            'select' => ['DATE_INSERT', 'PRICE'],
            'order' => ['DATE_INSERT' => 'ASC']
        ];

        $result = Order::getList($parameters);
        while ($row = $result->fetch()) {
            if ($row['DATE_INSERT'] instanceof Type\DateTime) {
                if ($this->isFilterYear) {
                    $key = $row['DATE_INSERT']->format('n');
                } else {
                    $key = $row['DATE_INSERT']->format('Y-m-d');
                }
                $rows[$key] += $row['PRICE'];
                $this->sumShop += $row['PRICE'];
            }
        }

        return array_values($rows);
    }

    private function getDatesBackward(): array
    {
        $dates = [];

        $endDate = new DateTime(); // текущая дата и время
        $startDate = clone $endDate;
        $startDate->modify('-' . $this->filterPeriod . ' days');

        $interval = new DateInterval('P1D'); // интервал в 1 день
        $dateRange = new DatePeriod($startDate, $interval, $endDate);

        /** @var */
        foreach ($dateRange as $date) {
            $bitrixDate = new Type\DateTime($date->format('Y-m-d'), 'Y-m-d');
            $this->category[$date->format('Y-m-d')] = HelperDate::getRussianDayName($bitrixDate);
            $dates[$date->format('Y-m-d')] = 0;
        }

        // текущий день
        $this->category[(new DateTime())->format('Y-m-d')] = HelperDate::getRussianDayName(new Type\DateTime());
        $dates[(new DateTime())->format('Y-m-d')] = 0;

        return $dates;
    }

    private function getOrderOzon(): array
    {
        $rows = [];

        if ($this->isFilterYear) {
            for ($i = 1; $i <= 12; $i++) {
                $rows[$i] = 0;
            }
        } else {
            $rows = $this->getDatesBackward();
        }

        $parameters = [
            'filter' => [
                '>=DATE' => $this->filterStartDate,
                '<=DATE' => $this->filterEndDate,
            ],
            'select' => ['ID', 'DATE', 'SUM']
        ];
        $result = SaleTable::getList($parameters);
        while ($row = $result->fetch()) {
            if ($row['DATE'] instanceof Type\DateTime) {
                if ($this->isFilterYear) {
                    $key = $row['DATE']->format('n');
                } else {
                    $key = $row['DATE']->format('Y-m-d');
                }

                $rows[$key] += $row['SUM'];
                $this->sumOzon += $row['SUM'];
            }
        }

        return array_values($rows);
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
