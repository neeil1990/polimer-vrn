<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\Localization\Loc;
use Darneo\Ozon\Export\Table\ProductListTable;

class OzonDataCatalogComponent extends CBitrixComponent
{
    private static array $moduleNames = ['darneo.ozon'];
    private array $defaultUrlTemplates404 = [];
    private array $componentVariables = [];
    private string $page = '';

    public function executeComponent()
    {
        try {
            $this->loadModules();
            $this->setSefDefaultParams();
            $this->getResult();
            $this->includeComponentTemplate($this->page);
        } catch (Exception $e) {
            ShowError($e->getMessage());
        }
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

    private function setSefDefaultParams(): void
    {
        $this->defaultUrlTemplates404 = [
            'list' => '',
        ];
        $this->componentVariables = ['ELEMENT_ID'];
    }

    private function getResult(): void
    {
        global $APPLICATION;

        $urlTemplates = [];
        $variables = [];
        if ($this->arParams['SEF_MODE'] === 'Y') {
            $urlTemplates = CComponentEngine::makeComponentUrlTemplates(
                $this->defaultUrlTemplates404,
                $this->arParams['SEF_URL_TEMPLATES']
            );
            $variableAliases = CComponentEngine::makeComponentVariableAliases(
                $this->defaultUrlTemplates404,
                $this->arParams['VARIABLE_ALIASES']
            );
            $this->page = CComponentEngine::parseComponentPath(
                $this->arParams['SEF_FOLDER'],
                $urlTemplates,
                $variables
            );

            if (strlen($this->page) <= 0) {
                $this->page = 'list';
            }
            CComponentEngine::initComponentVariables(
                $this->page,
                $this->componentVariables,
                $variableAliases,
                $variables
            );
        } else {
            $variableAliases = CComponentEngine::makeComponentVariableAliases(
                [],
                $this->arParams['VARIABLE_ALIASES']
            );

            CComponentEngine::initComponentVariables(
                false,
                $this->componentVariables,
                $variableAliases,
                $variables
            );

            if ((int)$variables['ELEMENT_ID'] > 0) {
                $this->page = 'detail';
            } else {
                $this->page = 'list';
            }
        }

        if ((int)$variables['ELEMENT_ID'] > 0) {
            $settings = $this->getSettings($variables['ELEMENT_ID']);
            $APPLICATION->SetTitle($settings['TITLE']);
        }

        if ($this->page !== 'list' && empty($settings)) {
            LocalRedirect($this->arParams['SEF_FOLDER']);
            exit;
        }

        $this->arResult['SEF_FOLDER'] = $this->arParams['SEF_FOLDER'];
        $this->arResult['URL_TEMPLATES'] = $urlTemplates;
        $this->arResult['VARIABLES'] = $variables;
        $this->arResult['ALIASES'] = $variableAliases;
    }

    private function getSettings(int $elementId): array
    {
        return ProductListTable::getById($elementId)->fetch() ?: [];
    }

    public function onPrepareComponentParams($arParams): array
    {
        $this->arParams = $arParams;

        return $this->arParams;
    }
}
