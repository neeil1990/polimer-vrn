<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Catalog\CatalogIblockTable;
use Bitrix\Catalog\StoreTable;
use Bitrix\Iblock\IblockTable;
use Bitrix\Iblock\PropertyEnumerationTable;
use Bitrix\Iblock\PropertyTable;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Security\Sign\Signer;
use Darneo\Ozon\Export\Filter\Property;
use Darneo\Ozon\Export\Helper\Compare as HelperCompare;
use Darneo\Ozon\Export\Table\StockListTable;
use Darneo\Ozon\Export\Table\StockFilterTable;
use Darneo\Ozon\Fields\Config\Stock as ConfigStock;
use Darneo\Ozon\Fields\Field;
use Darneo\Ozon\Fields\Value\Type\Date;
use Darneo\Ozon\Import\Table\StockTable;

class OzonExportStockDetailComponent extends CBitrixComponent
{
    private static array $moduleNames = ['darneo.ozon'];
    private static array $fieldsParams = [
        'SHOW' => [
            'DATE_CREATED',
            'TITLE',
            'IBLOCK',
            'VENDOR_CODE',
            'OZON_STOCK',
            'STORE',
            'MAX_COUNT',
            'MIN_COUNT',
            'FILTER',
            'DISABLE_OPTIMISATION'
        ]
    ];
    private array $fields;
    private array $fieldsSelect;
    private array $fieldsShow;
    private array $settings;
    private int $elementId;
    private int $iblockCatalogId;
    private int $iblockOffersId;
    private StockListTable $manager;

    public function executeComponent(): array
    {
        $result = [];
        try {
            $this->loadModules();
            $this->dataManager();
            switch ($this->arParams['ACTION']) {
                case 'list':
                    $this->initSettings();
                    $this->setTemplateData();
                    $result = $this->getActionResult(['STATUS' => 'SUCCESS']);
                    break;
                case 'update':
                    $status = $this->update();
                    $this->initSettings();
                    $this->setTemplateData();
                    $result = $this->getActionResult($status);
                    break;
                case 'delete':
                    $rowId = $this->request['rowId'];
                    $this->initSettings();
                    $status = $this->delete($rowId);
                    $this->setTemplateData();
                    $result = $this->getActionResult($status);
                    break;
                default:
                    $this->initSettings();
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
        $this->manager = new StockListTable();
        $this->fields = (new ConfigStock())->getFields();
        foreach (self::$fieldsParams['SHOW'] as $fieldName) {
            /** @var Field $field */
            $field = $this->fields[$fieldName];
            if ($field) {
                $this->fieldsShow[] = $fieldName;
            }
        }

        $this->fieldsSelect = $this->getFieldsSelect();
    }

    private function getFields($row): array
    {
        $result = [];

        foreach ($this->fieldsShow as $fieldName) {
            $field = $this->fields[$fieldName];
            /** @var Field $field */
            if ($field) {
                $data = [];
                $field->setValueFromDb($row);

                $data['CODE'] = $field->getInfo()->getName();
                $data['NAME'] = $field->getInfo()->getTitle();
                $data['HELPER_TEXT'] = $field->getHelperText();
                $data['SHOW'] = $this->getFieldShowHtml($field);

                if ($field->isEdit()) {
                    $data['EDIT']['VALUE'] = $this->getFieldEditHtml($field);
                    $data['EDIT']['HIDDEN'] = $this->getFieldHiddenValue($fieldName);
                }

                $result[$fieldName] = $data;
            }
        }

        return $result;
    }

    private function getFieldShowHtml($field): string
    {
        /** @var Field $field */
        $value = $field->getValue()->get();
        $html = $value ? $field->getShowView()->getHtml() : '[---]';

        return $html;
    }

    private function getFieldEditHtml($field)
    {
        $valueId = $field->getValue()->get();
        switch ($field->getInfo()->getName()) {
            case 'IBLOCK_ID':
                $valueId = $valueId ?: 0;
                $value = $this->getIblockCatalog($valueId);
                break;
            case 'VENDOR_CODE':
                $value = $field->getValue()->getRaw();
                $type = $value['VENDOR_CODE'] ?: '';
                $value = $this->getSettingsVendorCode($type);
                break;
            case 'OZON_STOCK_ID':
                $valueId = $valueId ?: 0;
                $value = $this->getOzonStock($valueId);
                break;
            case 'STORE_ID':
                $value = $field->getValue()->getRaw();
                $value = $this->getStore($value ?: []);
                break;
            case 'FILTER_PROP':
                $value = $field->getValue()->getRaw();

                $settingFilterList = $this->getSettingFilterList($value);
                $settingPropertyList = $this->getSettingsPropertyList();

                $propertyIds = [];
                foreach ($settingPropertyList as $id => $item) {
                    if ($item['PROPERTY_TYPE'] === 'L') {
                        $propertyIds[] = $id;
                    }
                }
                $settingPropertyList = array_values($settingPropertyList);

                $value = [
                    'PROPERTY_LIST' => $settingPropertyList,
                    'FILTER_LIST' => $settingFilterList,
                    'ENUM_LIST' => $propertyIds ? $this->getPropertyEnum($propertyIds) : [],
                    'TYPE' => [
                        'NUMBER' => HelperCompare::getNumber(),
                        'STRING' => HelperCompare::getString(),
                        'ENUM' => HelperCompare::getEnum(),
                        'ELEMENT' => HelperCompare::getEnum(),
                    ]
                ];
                break;
            default:
                if ($field->getValue() instanceof Date) {
                    $field->getValue()->setDateType('full');
                }
                $value = $field->getValue()->getRaw();
                break;
        }

        return $value;
    }

    private function getIblockCatalog(int $selectId = 0): array
    {
        $rows = [];
        $parameters = [
            'filter' => [
                'ID' => $this->getCatalogIblockIds(),
                'ACTIVE' => 'Y',
            ],
            'select' => ['ID', 'NAME'],
            'order' => ['ID' => 'ASC'],
        ];
        $result = IblockTable::getList($parameters);
        while ($row = $result->fetch()) {
            $row['SELECTED'] = $selectId === (int)$row['ID'];
            $rows[$row['ID']] = $row;
        }

        return $rows;
    }

    private function getCatalogIblockIds(): array
    {
        $rows = [];
        $parameters = [
            'filter' => [
                'PRODUCT_IBLOCK_ID' => false
            ],
            'select' => ['IBLOCK_ID'],
        ];
        $result = CatalogIblockTable::getList($parameters);
        while ($row = $result->fetch()) {
            $rows[] = $row['IBLOCK_ID'];
        }

        return $rows;
    }

    private function getList(): array
    {
        $rows = [];
        $parameters = [
            'filter' => [
                'ID' => $this->elementId
            ],
            'select' => $this->fieldsSelect,
            'order' => ['ID' => 'DESC']
        ];
        $result = StockListTable::getList($parameters);
        if ($row = $result->fetch()) {
            $rows = $this->getFields($row);
        }

        return $rows;
    }

    private function getSettingsVendorCode(string $code): array
    {
        $rows = [];
        $parameters = [
            'filter' => [
                'IBLOCK_ID' => [$this->iblockCatalogId, $this->iblockOffersId],
                'ACTIVE' => 'Y',
                'PROPERTY_TYPE' => 'S',
                'MULTIPLE' => 'N'
            ],
            'select' => ['ID', 'CODE', 'NAME', 'IBLOCK_ID'],
            'order' => ['IBLOCK_ID' => 'ASC', 'SORT' => 'ASC', 'NAME' => 'ASC'],
        ];
        $result = PropertyTable::getList($parameters);
        while ($row = $result->fetch()) {
            $row['SELECTED'] = $code === $row['ID'];
            $prefix = (int)$row['IBLOCK_ID'] === $this->iblockOffersId ? 'OFFERS: ' : 'CATALOG: ';
            $rows[] = [
                'ID' => $row['ID'],
                'CODE' => $row['CODE'],
                'NAME' => $prefix . $row['NAME'],
                'SELECTED' => $row['SELECTED'],
            ];
        }

        return $rows;
    }

    private function getOzonStock(int $selectId = 0): array
    {
        $rows = [];
        $parameters = [
            'select' => ['ID', 'NAME'],
            'order' => ['ID' => 'ASC'],
            'cache' => ['ttl' => 86400]
        ];
        $result = StockTable::getList($parameters);
        while ($row = $result->fetch()) {
            $row['SELECTED'] = $selectId === (int)$row['ID'];
            $rows[$row['ID']] = $row;
        }

        return $rows;
    }

    private function getSettingFilterList(int $elementId): array
    {
        $rows = [];

        $parameters = [
            'filter' => [
                'ELEMENT_ID' => $elementId
            ],
            'select' => [
                'ID',
                'PROP_ID',
                'COMPARE_TYPE',
                'COMPARE_VALUE',
            ],
            'cache' => ['ttl' => 86400]
        ];

        $result = StockFilterTable::getList($parameters);

        while ($row = $result->fetch()) {
            $rows[] = $row;
        }

        return $rows;
    }

    private function getStore(array $selectId = []): array
    {
        $rows = [];
        $parameters = [
            'select' => ['ID', 'NAME' => 'TITLE'],
            'order' => ['ID' => 'ASC'],
            'cache' => ['ttl' => 86400]
        ];
        $result = StoreTable::getList($parameters);
        while ($row = $result->fetch()) {
            $row['SELECTED'] = in_array($row['ID'], $selectId, true);
            $rows[$row['ID']] = $row;
        }

        return $rows;
    }

    private function getSettingsPropertyList(): array
    {
        $rows = [];
        $parameters = [
            'filter' => [
                'IBLOCK_ID' => [$this->iblockCatalogId],
                'ACTIVE' => 'Y',
                'PROPERTY_TYPE' => [
                    PropertyTable::TYPE_STRING,
                    PropertyTable::TYPE_NUMBER,
                    PropertyTable::TYPE_LIST,
                    PropertyTable::TYPE_ELEMENT
                ],
                'MULTIPLE' => 'N'
            ],
            'select' => ['ID', 'CODE', 'NAME', 'PROPERTY_TYPE', 'IBLOCK_ID'],
            'order' => ['IBLOCK_ID' => 'ASC', 'SORT' => 'ASC', 'NAME' => 'ASC'],
        ];
        $result = PropertyTable::getList($parameters);
        while ($row = $result->fetch()) {
            $prefix = (int)$row['IBLOCK_ID'] === $this->iblockOffersId ? 'OFFERS: ' : 'CATALOG: ';
            $rows[$row['ID']] = [
                'ID' => $row['ID'],
                'CODE' => $row['CODE'],
                'NAME' => $prefix . $row['NAME'],
                'PROPERTY_TYPE' => $row['PROPERTY_TYPE']
            ];
        }

        return $rows;
    }

    private function getPropertyEnum(array $propertyIds): array
    {
        $rows = [];
        $parameters = [
            'filter' => [
                'PROPERTY_ID' => $propertyIds,
            ],
            'select' => ['ID', 'PROPERTY_ID', 'VALUE'],
            'order' => ['SORT' => 'ASC', 'VALUE' => 'ASC'],
        ];
        $result = PropertyEnumerationTable::getList($parameters);
        while ($row = $result->fetch()) {
            $rows[] = [
                'ID' => $row['ID'],
                'PROPERTY_ID' => $row['PROPERTY_ID'],
                'NAME' => $row['VALUE']
            ];
        }

        return $rows;
    }

    private function getFieldHiddenValue($fieldName): string
    {
        $signer = new Signer();
        $params = array_merge($this->arParams, ['FIELD_NAME' => $fieldName]);
        $params = base64_encode(serialize($params));

        return $signer->sign($params, 'darneo.ozon.export.stock.detail');
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

    private function initSettings(): void
    {
        $this->settings = StockListTable::getById($this->elementId)->fetch();
        $this->iblockCatalogId = $this->settings['IBLOCK_ID'] ?: 0;
        $this->iblockOffersId = $this->getOffersIblockId($this->iblockCatalogId);
    }

    private function getOffersIblockId(int $iblockId): int
    {
        $parameters = [
            'filter' => [
                'PRODUCT_IBLOCK_ID' => $iblockId
            ],
            'select' => ['IBLOCK_ID'],
            'cache' => ['ttl' => 86400]
        ];
        $result = CatalogIblockTable::getList($parameters);
        if ($row = $result->fetch()) {
            return $row['IBLOCK_ID'];
        }

        return 0;
    }

    private function setTemplateData(): void
    {
        $row = $this->getList();
        $findCount = $this->getFindCount();
        $findCount = number_format($findCount, 0, '.', ' ');
        $this->arResult['DATA_VUE'] = [
            'FIELDS' => $row,
            'FIND' => $findCount,
            'ELEMENT_ID' => $this->elementId
        ];

        $this->arResult['PATH_TO_AJAX'] = $this->getPath() . '/ajax.php';
        $this->arResult['SIGNED_PARAMS'] = (new Signer())->sign(
            base64_encode(serialize($this->arParams)),
            'darneo.ozon.export.stock.detail'
        );
    }

    private function getFindCount(): int
    {
        if ($this->settings) {
            $settingFilterList = $this->getSettingFilterList($this->elementId);

            $filter = [['IBLOCK_ID' => $this->iblockCatalogId, 'ACTIVE' => 'Y']];
            foreach ($settingFilterList as $settingFilter) {
                $propId = $settingFilter['PROP_ID'];
                $compareType = $settingFilter['COMPARE_TYPE'];
                $compareValue = $settingFilter['COMPARE_VALUE'];
                $filter[] = (new Property($propId, $compareType, $compareValue))->get();
            }

            $mFilter = [];
            foreach ($filter as $item) {
                foreach ($item as $name => $val) {
                    $mFilter[$name][] = $val;
                }
            }

            $result = CIBlockElement::GetList(['SORT' => 'ASC'], $mFilter, false, false, ['ID']);
            return $result->SelectedRowsCount();
        }

        return 0;
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

        switch ($this->arParams['FIELD_NAME']) {
            case 'FILTER':

                $settingFilterList = $this->getSettingFilterList($this->elementId);

                $currentIds = [];
                foreach ($settingFilterList as $item) {
                    $currentIds[] = $item['ID'];
                }

                $newIds = [];
                $errors = [];

                foreach ($params['FILTER_PROP'] as $filterRow) {
                    if (in_array($filterRow['ID'], $currentIds, true)) {
                        $result = StockFilterTable::update(
                            $filterRow['ID'],
                            [
                                'ELEMENT_ID' => $this->elementId,
                                'PROP_ID' => $filterRow['PROP_ID'],
                                'COMPARE_TYPE' => $filterRow['COMPARE_TYPE'],
                                'COMPARE_VALUE' => $filterRow['COMPARE_VALUE'],
                            ]
                        );
                        if ($result->isSuccess()) {
                            $newIds[] = (int)$result->getId();
                        } else {
                            $errors[] = $result->getErrorMessages();
                        }
                    } else {
                        $result = StockFilterTable::add(
                            [
                                'ELEMENT_ID' => $this->elementId,
                                'PROP_ID' => $filterRow['PROP_ID'],
                                'COMPARE_TYPE' => $filterRow['COMPARE_TYPE'],
                                'COMPARE_VALUE' => $filterRow['COMPARE_VALUE'],
                            ]
                        );
                        if ($result->isSuccess()) {
                            $newIds[] = (int)$result->getId();
                        } else {
                            $errors[] = $result->getErrorMessages();
                        }
                    }
                }

                foreach ($currentIds as $currentId) {
                    if (!in_array((int)$currentId, $newIds, true)) {
                        StockFilterTable::delete($currentId);
                    }
                }

                if ($errors) {
                    $errors = array_merge(...$errors);
                    return [
                        'STATUS' => 'ERROR',
                        'ERROR_LIST' => $errors,
                    ];
                }

                return [
                    'STATUS' => 'SUCCESS',
                    'MESSAGE' => Loc::getMessage('DARNEO_OZON_MODULE_STOCK_DETAIL_UPDATE_SUCCESS'),
                ];
            default:
                if (!empty($params)) {
                    $result = $this->manager::update($this->elementId, $params);
                }
        }

        if ($result !== null) {
            if ($result->isSuccess()) {
                $status['STATUS'] = 'SUCCESS';
                $status['MESSAGE'] = Loc::getMessage('DARNEO_OZON_MODULE_STOCK_DETAIL_UPDATE_SUCCESS');
            } else {
                $status['STATUS'] = 'ERROR';
                $status['ERROR_LIST'] = $result->getErrorMessages();
            }
        } else {
            $status['STATUS'] = 'ERROR';
            $status['ERROR_LIST'] = [Loc::getMessage('DARNEO_OZON_MODULE_STOCK_DETAIL_UPDATE_ERROR')];
        }

        return $status;
    }

    private function delete(int $rowId): array
    {
        $result = StockListTable::delete($rowId);
        if ($result->isSuccess()) {
            return ['STATUS' => 'SUCCESS', 'REDIRECT' => $this->arParams['SEF_FOLDER']];
        }

        return ['STATUS' => 'ERROR', 'ERROR_LIST' => $result->getErrorMessages()];
    }

    public function onPrepareComponentParams($arParams): array
    {
        $this->elementId = $arParams['ELEMENT_ID'];
        $this->arParams = $arParams;

        return $this->arParams;
    }
}
