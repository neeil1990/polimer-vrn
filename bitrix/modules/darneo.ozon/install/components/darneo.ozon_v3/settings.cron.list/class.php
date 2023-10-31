<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Security\Sign\Signer;
use Bitrix\Main\Type;
use Darneo\Ozon\Install\SettingsCron;
use Darneo\Ozon\Main\Helper\Cron;
use Darneo\Ozon\Main\Table\SettingsCronTable;

class OzonSettingsCronListComponent extends CBitrixComponent
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
                case 'update':
                    $code = $this->request['code'] ?: '';
                    $value = (bool)$this->request['value'];
                    $status = $this->updateRow($code, $value);
                    $this->setTemplateData();
                    $result = $this->getActionResult($status);
                    break;
                case 'reinstall':
                    $status = $this->reinstall();
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
        $this->arResult['DATA_VUE'] = [
            'ITEMS' => $rows
        ];

        $this->arResult['PATH_TO_AJAX'] = $this->getPath() . '/ajax.php';
        $this->arResult['SIGNED_PARAMS'] = (new Signer())->sign(
            base64_encode(serialize($this->arParams)),
            'darneo.ozon.settings.cron.list'
        );
    }

    private function getList(): array
    {
        $rows = [];
        $parameters = [
            'select' => ['CODE', 'DATE_START', 'DATE_FINISH', 'VALUE', 'IS_STARTED'],
            'order' => ['SORT' => 'ASC']
        ];
        $result = SettingsCronTable::getList($parameters);
        while ($row = $result->fetch()) {
            $isError = false;
            if ($row['DATE_START'] instanceof Type\DateTime) {
                if ($row['IS_STARTED']) {
                    if ((new Type\DateTime()) > $row['DATE_START']->add('60 minutes')) {
                        $isError = true;
                    }
                }

                $row['DATE_START'] = $row['DATE_START']->format('d.m.Y H:i');
            }
            if ($row['DATE_FINISH'] instanceof Type\DateTime) {
                $row['DATE_FINISH'] = $row['DATE_FINISH']->format('d.m.Y H:i');
            }

            $row['IS_ERROR'] = $isError;

            $lang = Cron::getLang($row['CODE']);
            $row['TITLE'] = $lang ? $lang['TITLE'] : '';
            $row['DESCRIPTION'] = $lang ? $lang['DESCRIPTION'] : '';
            $row['HELPER'] = $lang ? $lang['HELPER'] : '';

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

    private function updateRow(string $code, bool $value): array
    {
        $result = SettingsCronTable::update($code, ['VALUE' => $value]);

        if ($result->isSuccess()) {
            return ['STATUS' => 'SUCCESS', 'MESSAGE' => Loc::getMessage('DARNEO_OZON_MODULE_CRON_LIST_UPDATE_SUCCESS')];
        }

        return ['STATUS' => 'ERROR', 'MESSAGE' => implode(', ', $result->getErrorMessages())];
    }

    private function reinstall(): array
    {
        $result = SettingsCronTable::getList();
        while ($row = $result->fetch()) {
            SettingsCronTable::delete($row['CODE']);
        }

        (new SettingsCron())->setValue();

        return ['STATUS' => 'SUCCESS', 'MESSAGE' => Loc::getMessage('DARNEO_OZON_MODULE_CRON_LIST_UPDATE_REINSTALL')];
    }

    public function onPrepareComponentParams($arParams): array
    {
        $this->arParams = $arParams;

        return $this->arParams;
    }
}
