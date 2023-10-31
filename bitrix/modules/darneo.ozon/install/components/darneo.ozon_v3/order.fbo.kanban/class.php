<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Security\Sign\Signer;
use Darneo\Ozon\Order\Table\FboListTable;

class OzonOrderFboKanbanComponent extends CBitrixComponent
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
        $elements = $this->getList();

        $this->arResult['DATA_VUE'] = [
            'LIST' => $elements
        ];

        $this->arResult['PATH_TO_AJAX'] = $this->getPath() . '/ajax.php';
        $this->arResult['SIGNED_PARAMS'] = (new Signer())->sign(
            base64_encode(serialize($this->arParams)),
            'darneo.ozon.order.fbo.kanban'
        );
    }

    private function getList(): array
    {
        $rows = [];
        $parameters = [
            'filter' => [
                'STATUS' => ['awaiting_packaging', 'awaiting_deliver', 'delivering']
            ],
            'select' => [
                'ID',
                'DATE_CREATED',
                'STATUS',
                'POSTING_NUMBER',
                'PRODUCTS',
                'FINANCIAL',
            ],
            'order' => ['DATE_CREATED' => 'DESC'],
        ];

        $result = FboListTable::getList($parameters);
        while ($row = $result->fetch()) {
            if ($row['DATE_CREATED'] instanceof \Bitrix\Main\Type\DateTime) {
                $row['DATE_CREATED'] = $row['DATE_CREATED']->format('d.m.Y H:i');
            }

            $sum = 0;
            foreach ($row['FINANCIAL']['products'] as $product) {
                $sum += $product['price'];
            }
            $sumFormated = number_format($sum, 0, '.', ' ');
            $row['SUM'] = $sum;
            $row['SUM_FORMATED'] = $sumFormated;

            $rows[$row['STATUS']]['SUM'] += $row['SUM'];
            $rows[$row['STATUS']]['SUM_FORMATED'] = number_format($rows[$row['STATUS']]['SUM'], 0, '.', ' ');
            $rows[$row['STATUS']]['ITEMS'][] = $row;
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
