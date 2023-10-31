<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Security\Sign\Signer;
use Darneo\Ozon\Order\Fbs\Import as FbsImport;

class OzonOrderFbsListExchangeComponent extends CBitrixComponent
{
    private static array $moduleNames = ['darneo.ozon'];
    protected int $limit = 1000;
    protected int $page = 0;
    protected int $totalCount = 0;
    private FbsImport $manager;
    private bool $isStart = false;
    private array $errors = [];

    public function executeComponent(): array
    {
        $result = [];
        try {
            $this->loadModules();
            $this->dataManager();
            switch ($this->arParams['ACTION']) {
                case 'start':
                    $this->isStart = true;
                    $this->page = $this->request['page'] ?: $this->page;
                    $this->setTemplateData();
                    if ($this->errors) {
                        $status = ['STATUS' => 'ERROR', 'ERROR_LIST' => $this->errors];
                    } else {
                        $status = ['STATUS' => 'SUCCESS'];
                    }
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
        $this->manager = new FbsImport();
        $this->totalCount = $this->manager->getDataCount();
    }

    private function setTemplateData(): void
    {
        $countAll = number_format($this->totalCount, 0, '.', ' ');
        $countCurrent = number_format($this->page * $this->limit, 0, '.', ' ');

        $isFinish = false;
        if ($this->isStart) {
            $isFinish = !$this->initSend();
            $this->errors = $this->getErrorImport();
        }

        if ($isFinish) {
            $this->page = 0;
        }

        $this->arResult['DATA_VUE'] = [
            'COUNT_HELPER' => Loc::getMessage('DARNEO_OZON_MODULE_ORDER_LIST_EXCHANGE_COUNT_HELPER'),
            'STATUS_HELPER' => Loc::getMessage('DARNEO_OZON_MODULE_ORDER_LIST_EXCHANGE_STATUS_HELPER'),
            'PAGE' => $this->page,
            'COUNT_ALL' => $this->totalCount,
            'COUNT_ALL_FORMATED' => $countAll,
            'COUNT_CURRENT' => $this->page * $this->limit,
            'COUNT_CURRENT_FORMATED' => $countCurrent,
            'FINISHED' => $isFinish
        ];

        $this->arResult['PATH_TO_AJAX'] = $this->getPath() . '/ajax.php';
        $this->arResult['SIGNED_PARAMS'] = (new Signer())->sign(
            base64_encode(serialize($this->arParams)),
            'darneo.ozon.order.fbs.list.exchange'
        );
    }

    private function initSend(): bool
    {
        return $this->manager->initData($this->page, $this->limit);
    }

    private function getErrorImport(): array
    {
        return $this->manager->getErrors();
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
