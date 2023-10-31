<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Catalog\CatalogIblockTable;
use Bitrix\Catalog\GroupTable;
use Bitrix\Iblock\IblockTable;
use Bitrix\Iblock\PropertyEnumerationTable;
use Bitrix\Iblock\PropertyTable;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Security\Sign\Signer;
use Bitrix\Main\SiteTable;
use Darneo\Ozon\Export\Filter\Property;
use Darneo\Ozon\Export\Helper\Compare as HelperCompare;
use Darneo\Ozon\Export\Helper\Dimension as HelperDimension;
use Darneo\Ozon\Export\Product\Dimension;
use Darneo\Ozon\Export\Table\ProductFilterTable;
use Darneo\Ozon\Export\Table\ProductListTable;
use Darneo\Ozon\Fields\Config\Product as ConfigProduct;
use Darneo\Ozon\Fields\Field;
use Darneo\Ozon\Fields\Value\Type\Date;

class OzonExportProductDetailComponent extends CBitrixComponent
{
    private static array $moduleNames = ['darneo.ozon'];
    private static array $fieldsParams = [
        'SHOW' => [
            'DATE_CREATED',
            'TITLE',
            'IBLOCK',
            'PHOTO_MAIN',
            'PHOTO_OTHER',
            'ELEMENT_NAME',
            'VENDOR_CODE',
            'BAR_CODE',
            'FILTER',
            'DOMAIN',
            'TYPE_PRICE_ID',
            'SITE_ID',
            'IS_DISCOUNT_PRICE',
            'PRICE_RATIO',
            'WEIGHT',
            'WIDTH',
            'HEIGHT',
            'LENGTH',
            'DIMENSION_UNIT',
            'WEIGHT_UNIT',
        ]
    ];
    private array $fields;
    private array $fieldsSelect;
    private array $fieldsShow;
    private array $settings;
    private int $elementId;
    private int $iblockCatalogId;
    private int $iblockOffersId;
    private ProductListTable $manager;

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
        $this->manager = new ProductListTable();
        $this->fields = (new ConfigProduct())->getFields();
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
            case 'TYPE_PRICE_ID':
                $valueId = $valueId ?: 0;
                $value = $this->getSettingsPrice($valueId);
                break;
            case 'SITE_ID':
                $valueId = $valueId ?: 0;
                $value = $this->getSite($valueId);
                break;
            case 'PHOTO_MAIN':
                $value = $field->getValue()->getRaw();
                $type = $value['PHOTO_MAIN'] ?: '';
                $value = $this->getSettingsMainPhoto($type);
                break;
            case 'PHOTO_OTHER':
                $value = $field->getValue()->getRaw();
                $type = $value['PHOTO_OTHER'] ?: '';
                $value = $this->getSettingsMainOther($type);
                break;
            case 'ELEMENT_NAME':
                $value = $field->getValue()->getRaw();
                $type = $value['ELEMENT_NAME'] ?: '';
                $value = $this->getSettingsProperty($type);
                break;
            case 'VENDOR_CODE':
                $value = $field->getValue()->getRaw();
                $type = $value['VENDOR_CODE'] ?: '';
                $value = $this->getSettingsProperty($type);
                break;
            case 'WEIGHT':
                $value = $field->getValue()->getRaw();
                $type = $value['WEIGHT'] ?: '';
                $value = $this->getSettingsProperty($type);
                break;
            case 'WIDTH':
                $value = $field->getValue()->getRaw();
                $type = $value['WIDTH'] ?: '';
                $value = $this->getSettingsProperty($type);
                break;
            case 'HEIGHT':
                $value = $field->getValue()->getRaw();
                $type = $value['HEIGHT'] ?: '';
                $value = $this->getSettingsProperty($type);
                break;
            case 'LENGTH':
                $value = $field->getValue()->getRaw();
                $type = $value['LENGTH'] ?: '';
                $value = $this->getSettingsProperty($type);
                break;
            case 'DIMENSION_UNIT':
                $value = $field->getValue()->getRaw();
                $value = $this->getDimensionUnit($value);
                break;
            case 'WEIGHT_UNIT':
                $value = $field->getValue()->getRaw();
                $value = $this->getWeightUnit($value);
                break;
            case 'BAR_CODE':
                $value = $field->getValue()->getRaw();
                $type = $value['BAR_CODE'] ?: '';
                $value = $this->getSettingsProperty($type);
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
        $result = ProductListTable::getList($parameters);
        if ($row = $result->fetch()) {
            $rows = $this->getFields($row);
        }

        return $rows;
    }

    private function getSettingsPrice(int $priceId = 0): array
    {
        $rows = [];
        $parameters = [
            'select' => ['ID', 'NAME', 'LANG_CURRENT' => 'CURRENT_LANG.NAME'],
            'order' => ['ID' => 'DESC']
        ];
        $result = GroupTable::getList($parameters);
        while ($row = $result->fetch()) {
            $row['SELECTED'] = $priceId === (int)$row['ID'];
            $rows[] = [
                'ID' => $row['ID'],
                'CODE' => $row['NAME'],
                'NAME' => $row['LANG_CURRENT'],
                'SELECTED' => $row['SELECTED'],
            ];
        }

        return $rows;
    }

    private function getSite(string $siteId = ''): array
    {
        $rows = [];
        $parameters = [
            'filter' => ['ACTIVE' => 'Y'],
            'select' => ['LID', 'NAME'],
            'order' => ['SORT' => 'ASC', 'LID' => 'DESC'],
        ];
        $result = SiteTable::getList($parameters);
        while ($row = $result->fetch()) {
            $row['SELECTED'] = $siteId === $row['LID'];
            $rows[] = [
                'ID' => $row['LID'],
                'NAME' => $row['NAME'],
                'SELECTED' => $row['SELECTED'],
            ];
        }

        return $rows;
    }

    private function getSettingsMainPhoto(string $code = ''): array
    {
        $rows = [
            [
                'ID' => 'CATALOG_PREVIEW_PICTURE',
                'NAME' => Loc::getMessage('DARNEO_OZON_MODULE_PRODUCT_DETAIL_CATALOG_PREVIEW_PICTURE'),
                'SELECTED' => $code === 'CATALOG_PREVIEW_PICTURE'
            ],
            [
                'ID' => 'CATALOG_DETAIL_PICTURE',
                'NAME' => Loc::getMessage('DARNEO_OZON_MODULE_PRODUCT_DETAIL_CATALOG_DETAIL_PICTURE'),
                'SELECTED' => $code === 'CATALOG_DETAIL_PICTURE'
            ],
        ];

        if ($this->iblockOffersId > 0) {
            $rows[] = [
                'ID' => 'OFFERS_PREVIEW_PICTURE',
                'NAME' => Loc::getMessage('DARNEO_OZON_MODULE_PRODUCT_DETAIL_OFFERS_PREVIEW_PICTURE'),
                'SELECTED' => $code === 'OFFERS_PREVIEW_PICTURE'
            ];
            $rows[] = [
                'ID' => 'OFFERS_DETAIL_PICTURE',
                'NAME' => Loc::getMessage('DARNEO_OZON_MODULE_PRODUCT_DETAIL_OFFERS_DETAIL_PICTURE'),
                'SELECTED' => $code === 'OFFERS_DETAIL_PICTURE'
            ];
        }

        $parameters = [
            'filter' => [
                'IBLOCK_ID' => [$this->iblockCatalogId, $this->iblockOffersId],
                'ACTIVE' => 'Y',
                'PROPERTY_TYPE' => 'F',
                'MULTIPLE' => 'N',
            ],
            'select' => ['ID', 'CODE', 'NAME', 'IBLOCK_ID'],
            'order' => ['IBLOCK_ID' => 'ASC', 'SORT' => 'ASC', 'NAME' => 'ASC'],
        ];
        $result = PropertyTable::getList($parameters);
        while ($row = $result->fetch()) {
            $row['SELECTED'] = $code === $row['ID'];
            $isOffers = (int)$row['IBLOCK_ID'] === $this->iblockOffersId;
            $prefix = $isOffers ? 'OFFERS: ' : 'CATALOG: ';
            $rows[] = [
                'ID' => $row['ID'],
                'CODE' => $row['CODE'],
                'NAME' => $prefix . $row['NAME'],
                'SELECTED' => $row['SELECTED'],
            ];
        }

        return $rows;
    }

    private function getSettingsMainOther(string $code): array
    {
        $rows = [];
        $parameters = [
            'filter' => [
                'IBLOCK_ID' => [$this->iblockCatalogId, $this->iblockOffersId],
                'ACTIVE' => 'Y',
                'PROPERTY_TYPE' => 'F',
                'MULTIPLE' => 'Y'
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

    private function getSettingsProperty(string $code): array
    {
        $rows = [];
        $parameters = [
            'filter' => [
                'IBLOCK_ID' => [$this->iblockCatalogId, $this->iblockOffersId],
                'ACTIVE' => 'Y',
                'PROPERTY_TYPE' => [
                    PropertyTable::TYPE_STRING,
                    PropertyTable::TYPE_NUMBER,
                    PropertyTable::TYPE_LIST,
                ],
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

    private function getDimensionUnit(string $code): array
    {
        $rows = [];
        $rows[] = [
            'ID' => Dimension::DIMENSION_UNIT_MM,
            'CODE' => Dimension::DIMENSION_UNIT_MM,
            'NAME' => HelperDimension::getTypeHTML(Dimension::DIMENSION_UNIT_MM),
            'SELECTED' => $code === Dimension::DIMENSION_UNIT_MM,
        ];
        $rows[] = [
            'ID' => Dimension::DIMENSION_UNIT_CM,
            'CODE' => Dimension::DIMENSION_UNIT_CM,
            'NAME' => HelperDimension::getTypeHTML(Dimension::DIMENSION_UNIT_CM),
            'SELECTED' => $code === Dimension::DIMENSION_UNIT_CM,
        ];
        $rows[] = [
            'ID' => Dimension::DIMENSION_UNIT_IN,
            'CODE' => Dimension::DIMENSION_UNIT_IN,
            'NAME' => HelperDimension::getTypeHTML(Dimension::DIMENSION_UNIT_IN),
            'SELECTED' => $code === Dimension::DIMENSION_UNIT_IN,
        ];

        return $rows;
    }

    private function getWeightUnit(string $code): array
    {
        $rows = [];
        $rows[] = [
            'ID' => Dimension::WEIGHT_UNIT_G,
            'CODE' => Dimension::WEIGHT_UNIT_G,
            'NAME' => HelperDimension::getTypeHTML(Dimension::WEIGHT_UNIT_G),
            'SELECTED' => $code === Dimension::WEIGHT_UNIT_G,
        ];
        $rows[] = [
            'ID' => Dimension::WEIGHT_UNIT_KG,
            'CODE' => Dimension::WEIGHT_UNIT_KG,
            'NAME' => HelperDimension::getTypeHTML(Dimension::WEIGHT_UNIT_KG),
            'SELECTED' => $code === Dimension::WEIGHT_UNIT_KG,
        ];
        $rows[] = [
            'ID' => Dimension::WEIGHT_UNIT_LB,
            'CODE' => Dimension::WEIGHT_UNIT_LB,
            'NAME' => HelperDimension::getTypeHTML(Dimension::WEIGHT_UNIT_LB),
            'SELECTED' => $code === Dimension::WEIGHT_UNIT_LB,
        ];

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

        $result = ProductFilterTable::getList($parameters);

        while ($row = $result->fetch()) {
            $rows[] = $row;
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

        return $signer->sign($params, 'darneo.ozon.export.product.detail');
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
        $this->settings = ProductListTable::getById($this->elementId)->fetch();
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
            'darneo.ozon.export.product.detail'
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
                        $result = ProductFilterTable::update(
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
                        $result = ProductFilterTable::add(
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
                        ProductFilterTable::delete($currentId);
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
                    'MESSAGE' => Loc::getMessage('DARNEO_OZON_MODULE_PRODUCT_DETAIL_UPDATE_SUCCESS'),
                ];
            default:
                if (!empty($params)) {
                    $result = $this->manager::update($this->elementId, $params);
                }
        }

        if ($result !== null) {
            if ($result->isSuccess()) {
                $status['STATUS'] = 'SUCCESS';
                $status['MESSAGE'] = Loc::getMessage('DARNEO_OZON_MODULE_PRODUCT_DETAIL_UPDATE_SUCCESS');
            } else {
                $status['STATUS'] = 'ERROR';
                $status['ERROR_LIST'] = $result->getErrorMessages();
            }
        } else {
            $status['STATUS'] = 'ERROR';
            $status['ERROR_LIST'] = [Loc::getMessage('DARNEO_OZON_MODULE_PRODUCT_DETAIL_UPDATE_ERROR')];
        }

        return $status;
    }

    private function delete(int $rowId): array
    {
        $result = ProductListTable::delete($rowId);
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
