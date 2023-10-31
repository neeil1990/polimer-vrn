<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Security\Sign\Signer;
use Darneo\Ozon\Api\v2;
use Darneo\Ozon\Api\v4;
use Darneo\Ozon\Export\Product\Cron\Manager;
use Darneo\Ozon\Export\Product\Manager as ExportProductManager;
use Darneo\Ozon\Export\Table\ProductListTable;
use Darneo\Ozon\Export\Table\ProductLogTable;

class OzonExportProductExchangeComponent extends CBitrixComponent
{
    private const TRIGGER_TMP = 'TMP';
    private const TRIGGER_MAIN = 'MAIN';
    private static array $moduleNames = ['darneo.ozon'];
    protected int $limit = 100;
    protected int $page = 0;
    protected int $totalCount = 0;
    private ExportProductManager $manager;
    private int $elementId;
    private array $settings;
    private bool $isStart = false;
    private string $trigger = '';

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
        $this->trigger = $this->request['trigger'] ?: self::TRIGGER_TMP;
        $this->manager = new ExportProductManager($this->elementId);
        switch ($this->trigger) {
            case self::TRIGGER_MAIN:
                $this->totalCount = $this->manager->getDataTmpCount();
                break;
            default:
                $this->totalCount = $this->manager->getDataIblockCount();
                break;
        }
        $this->settings = ProductListTable::getById($this->elementId)->fetch();
    }

    private function setTemplateData(): void
    {
        $countAll = number_format($this->totalCount, 0, '.', ' ');
        $countCurrent = number_format($this->page * $this->limit, 0, '.', ' ');

        if ($this->isStart) {
            $this->initSend();
        }

        $isFinish = $this->isFinish();
        if ($isFinish) {
            $this->page = 0;
        }

        $this->arResult['DATA_VUE'] = [
            'COUNT_HELPER' => Loc::getMessage('DARNEO_OZON_MODULE_PRODUCT_EXCHANGE_COUNT_HELPER_' . $this->trigger),
            'STATUS_HELPER' => Loc::getMessage('DARNEO_OZON_MODULE_PRODUCT_EXCHANGE_STATUS_HELPER_' . $this->trigger),
            'PAGE' => $this->page,
            'COUNT_ALL' => $this->totalCount,
            'COUNT_ALL_FORMATED' => $countAll,
            'COUNT_CURRENT' => $this->page * $this->limit,
            'COUNT_CURRENT_FORMATED' => $countCurrent,
            'FINISHED' => $isFinish,
            'TRIGGER' => $this->trigger,
            'EXPORT_LIMIT' => $this->getExportLimit(),
        ];

        $this->arResult['PATH_TO_AJAX'] = $this->getPath() . '/ajax.php';
        $this->arResult['SIGNED_PARAMS'] = (new Signer())->sign(
            base64_encode(serialize($this->arParams)),
            'darneo.ozon.export.product.exchange'
        );
    }

    private function initSend(): void
    {
        switch ($this->trigger) {
            case self::TRIGGER_TMP:
                $this->manager->initDataTmp($this->page, $this->limit);
                break;
            case self::TRIGGER_MAIN:
                $data = $this->manager->getDataOzon($this->page, $this->limit);
                foreach ($data as $elementId => $item) {
                    $answer = (new v2\Product())->import($item);
                    ProductLogTable::add(
                        [
                            'PRODUCT_ID' => $this->elementId,
                            'ELEMENT_ID' => $elementId,
                            'OFFER_ID' => $item['offer_id'],
                            'SEND_JSON' => $item,
                            'ANSWER' => $answer ?: [],
                        ]
                    );
                }
                break;
        }
    }

    private function isFinish(): bool
    {
        if ($this->isStart) {
            return $this->page * $this->limit >= $this->totalCount;
        }
        return false;
    }

    private function getExportLimit(): array
    {
        $info = (new v4\Product());
        $dataLimit = $info->infoLimit();

        $totalLimit = $dataLimit['total']['limit'] ?: 0;
        $totalUsage = $dataLimit['total']['usage'] ?: 0;
        if ($totalLimit > 0) {
            $totalLimitLang = Loc::getMessage(
                'DARNEO_OZON_MODULE_PRODUCT_EXCHANGE_STATUS',
                ['#COUNT#' => number_format($totalLimit, 0, '.', ' ')]
            );
        } else {
            $totalLimitLang = Loc::getMessage('DARNEO_OZON_MODULE_PRODUCT_EXCHANGE_UNDEFINED',);
        }

        $createLimit = $dataLimit['daily_create']['limit'] ?: 0;
        $createUsage = $dataLimit['daily_create']['usage'] ?: 0;
        if ($createLimit > 0) {
            $createLimitLang = Loc::getMessage(
                'DARNEO_OZON_MODULE_PRODUCT_EXCHANGE_STATUS',
                ['#COUNT#' => number_format($createLimit, 0, '.', ' ')]
            );
        } else {
            $createLimitLang = Loc::getMessage('DARNEO_OZON_MODULE_PRODUCT_EXCHANGE_UNDEFINED',);
        }

        $updateLimit = $dataLimit['daily_update']['limit'] ?: 0;
        $updateUsage = $dataLimit['daily_update']['usage'] ?: 0;
        if ($updateLimit > 0) {
            $updateLimitLang = Loc::getMessage(
                'DARNEO_OZON_MODULE_PRODUCT_EXCHANGE_STATUS',
                ['#COUNT#' => number_format($updateLimit, 0, '.', ' ')]
            );
        } else {
            $updateLimitLang = Loc::getMessage('DARNEO_OZON_MODULE_PRODUCT_EXCHANGE_UNDEFINED',);
        }

        return [
            'TOTAL' => [
                'LIMIT' => $totalLimit,
                'LIMIT_TEXT' => $totalLimitLang,
                'USAGE' => $totalUsage,
            ],
            'CREATE' => [
                'LIMIT' => $createLimit,
                'LIMIT_TEXT' => $createLimitLang,
                'USAGE' => $createUsage,
            ],
            'UPDATE' => [
                'LIMIT' => $updateLimit,
                'LIMIT_TEXT' => $updateLimitLang,
                'USAGE' => $updateUsage,
            ],
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
        $this->elementId = $arParams['ELEMENT_ID'];
        $this->arParams = $arParams;

        return $this->arParams;
    }
}
