<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Security\Sign\Signer;
use Darneo\Ozon\Fields\Config\Settings as ConfigSettings;
use Darneo\Ozon\Fields\Field;
use Darneo\Ozon\Main\Table\SettingsTable;

class OzonSettingsMainComponent extends CBitrixComponent
{
    private static array $moduleNames = ['darneo.ozon'];
    private static array $fieldsParams = [
        'SHOW' => [
            'IS_TEST',
            'IS_CHAT',
        ]
    ];
    private array $fields;
    private array $fieldsSelect;
    private array $fieldsShow;
    private SettingsTable $manager;

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
                    $status = $this->update();
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
        $this->manager = new SettingsTable();
        $this->fields = (new ConfigSettings())->getFields();
        foreach (self::$fieldsParams['SHOW'] as $fieldName) {
            /** @var Field $field */
            $field = $this->fields[$fieldName];
            if ($field) {
                $this->fieldsShow[] = $fieldName;
            }
        }

        $this->fieldsSelect = $this->getFieldsSelect();
    }

    private function getFieldsSelect(): array
    {
        $select = [];
        /** @var Field $field */
        foreach ($this->fields as $fieldName => $field) {
            if (in_array($fieldName, $this->fieldsShow, true)) {
                $select[] = $field->getSelect();
            }
        }
        $select = array_merge(...$select);

        return array_unique($select);
    }

    private function setTemplateData(): void
    {
        $rows = $this->getList();
        $this->arResult['DATA_VUE'] = [
            'FIELDS' => $rows
        ];

        $this->arResult['PATH_TO_AJAX'] = $this->getPath() . '/ajax.php';
        $this->arResult['SIGNED_PARAMS'] = (new Signer())->sign(
            base64_encode(serialize($this->arParams)),
            'darneo.ozon.settings.main'
        );
    }

    private function getList(): array
    {
        $rows = [];
        foreach ($this->fieldsShow as $fieldName) {
            $field = $this->fields[$fieldName];
            /** @var Field $field */
            if ($field) {
                $parameters = [
                    'filter' => ['CODE' => $fieldName],
                    'select' => $this->fieldsSelect
                ];
                $result = SettingsTable::getList($parameters);
                while ($row = $result->fetch()) {
                    $data = [];
                    $field->setValueFromDb($row);
                    $data['CODE'] = $field->getInfo()->getName();
                    $data['NAME'] = $field->getInfo()->getTitle();
                    $data['SHOW'] = $this->getFieldShowHtml($field);
                    if ($field->isEdit()) {
                        $data['EDIT']['VALUE'] = $this->getFieldEditHtml($field);
                        $data['EDIT']['HIDDEN'] = $this->getFieldHiddenValue($fieldName);
                    }
                    $rows[$fieldName] = $data;
                }
            }
        }

        return $rows;
    }

    private function getFieldShowHtml($field): string
    {
        $value = $field->getValue()->get();

        return $value ? $field->getShowView()->getHtml() : '[---]';
    }

    private function getFieldEditHtml($field)
    {
        $valueId = $field->getValue()->get();
        switch ($field->getInfo()->getName()) {
            default:
                $value = $field->getValue()->getRaw();
                break;
        }

        return $value;
    }

    private function getFieldHiddenValue($fieldName): string
    {
        $signer = new Signer();
        $params = array_merge($this->arParams, ['FIELD_NAME' => $fieldName]);
        $params = base64_encode(serialize($params));

        return $signer->sign($params, 'darneo.ozon.settings.main');
    }

    private function getActionResult(array $status): array
    {
        $result = [
            'DATA_VUE' => $this->arResult['DATA_VUE']
        ];

        return array_merge($status, $result);
    }

    private function update(): array
    {
        $status = [];
        $params = [];
        $result = null;

        /** @var Field $field */
        foreach ($this->fields as $field) {
            $fieldNameTable = $field->getInfo()->getName();
            $value = $this->request[$fieldNameTable];
            if ($value !== null) {
                $params[$fieldNameTable] = $value ?: '';
            }
        }

        if (!empty($params)) {
            $result = $this->manager::update($this->arParams['FIELD_NAME'], $params);
        }

        if ($result !== null) {
            if ($result->isSuccess()) {
                $status['STATUS'] = 'SUCCESS';
                $status['MESSAGE'] = Loc::getMessage('DARNEO_OZON_MODULE_SETTINGS_MAIN_UPDATE_SUCCESS');
            } else {
                $status['STATUS'] = 'ERROR';
                $status['ERROR_LIST'] = $result->getErrorMessages();
            }
        } else {
            $status['STATUS'] = 'ERROR';
            $status['ERROR_LIST'] = [Loc::getMessage('DARNEO_OZON_MODULE_SETTINGS_MAIN_UPDATE_ERROR')];
        }

        return $status;
    }

    public function onPrepareComponentParams($arParams): array
    {
        $this->arParams = $arParams;

        return $this->arParams;
    }
}
