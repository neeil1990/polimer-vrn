<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Application;
use Bitrix\Main\Entity\DataManager;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Security\Sign\Signer;
use Darneo\Ozon\Import\Core\Category;
use Darneo\Ozon\Import\Table\TreeTable;

class OzonSettingsCategoryImportComponent extends CBitrixComponent
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
                    $this->deleteCategoryData();
                    $this->importAttribute();
                    $this->setTemplateData();
                    $result = $this->getActionResult(['STATUS' => 'SUCCESS']);
                    break;
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

    private function deleteCategoryData(): void
    {
        $connection = Application::getConnection();
        $entitiesDataClasses = [
            TreeTable::class
        ];
        /** @var DataManager $entityDataClass */
        foreach ($entitiesDataClasses as $entityDataClass) {
            if ($connection->isTableExists($entityDataClass::getTableName())) {
                $connection->dropTable($entityDataClass::getTableName());
            }
            $entityDataClass::getEntity()->createDbTable();
        }
    }

    private function importAttribute(): void
    {
        (new Category())->start();
    }

    private function setTemplateData(): void
    {
        $this->arResult['DATA_VUE'] = [];

        $this->arResult['PATH_TO_AJAX'] = $this->getPath() . '/ajax.php';
        $this->arResult['SIGNED_PARAMS'] = (new Signer())->sign(
            base64_encode(serialize($this->arParams)),
            'darneo.ozon.data.category.list'
        );
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
