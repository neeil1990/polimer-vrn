<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Iblock\ElementTable;
use Bitrix\Iblock\PropertyEnumerationTable;
use Bitrix\Iblock\PropertyTable;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Security\Sign\Signer;
use Darneo\Ozon\Component\Attribute\AttributeValue;
use Darneo\Ozon\Export\Helper\AttributeType as HelperAttributeType;
use Darneo\Ozon\Export\Helper\PropertyType as HelperPropertyType;
use Darneo\Ozon\Export\Table\ConnectionCategoryPropertyTable;
use Darneo\Ozon\Export\Table\ConnectionPropertyRatioTable;
use Darneo\Ozon\Export\Table\ConnectionPropertyValueTable;
use Darneo\Ozon\Export\Table\ConnectionSectionTreeTable;
use Darneo\Ozon\Export\Table\ProductListTable;
use Darneo\Ozon\Import\Helper\Attribute as HelperAttribute;
use Darneo\Ozon\Import\Table\ConnectionPropCategoryTable;
use Darneo\Ozon\Import\Table\ConnectionPropValueTable;
use Darneo\Ozon\Import\Table\PropertyListTable;
use Darneo\Ozon\Main\Helper\Iblock as HelperIblock;

class OzonExportProductAttributeComponent extends CBitrixComponent
{
    private static array $moduleNames = ['darneo.ozon'];
    private int $connectionSectionTree;
    private array $connectionPropertyValue;
    private array $connectionPropertyRatio;
    private int $iblockCatalogId;
    private int $iblockOffersId;
    private int $categoryId;
    private int $propertyId;

    public function executeComponent(): array
    {
        $result = [];
        try {
            $this->loadModules();
            $this->dataManager();
            switch ($this->arParams['ACTION']) {
                case 'list':
                case 'tree':
                    $this->setTemplateData();
                    $result = $this->getActionResult(['STATUS' => 'SUCCESS']);
                    break;
                case 'bindProperty':
                    $propertyId = $this->request['propertyId'];
                    $propertyType = $this->request['propertyType'];
                    $propertyValue = $this->request['propertyValue'] ?: '';
                    $value = $this->request['value'] ?: '';
                    $status = $this->setCategoryProperty($propertyId, $propertyType, $propertyValue, $value);
                    $this->setTemplateData();
                    $result = $this->getActionResult($status);
                    break;
                case 'bindConnectionEnum':
                    $attributeId = $this->request['attributeId'];
                    $attributeValueId = $this->request['attributeValueId'];
                    $propertyId = $this->request['propertyId'] ?: 0;
                    $propertyEnumId = $this->request['propertyEnumId'] ?: 0;
                    $status = $this->setConnectionAttributeEnum(
                        $attributeId,
                        $attributeValueId,
                        $propertyId,
                        $propertyEnumId
                    );
                    $this->setTemplateData();
                    $result = $this->getActionResult($status);
                    break;
                case 'deleteConnectionEnum':
                    $connectionId = $this->request['connectionId'];
                    if ($connection = ConnectionPropertyValueTable::getById($connectionId)->fetch()) {
                        ConnectionPropertyValueTable::delete($connection['ID']);
                    }
                    $this->setTemplateData();
                    $result = $this->getActionResult(['STATUS' => 'SUCCESS']);
                    break;
                case 'search':
                    $search = $this->request['search'] ?: '';
                    $attributeId = $this->request['attributeId'] ?: 0;
                    $dataSearch = $this->searchEnumOzon($attributeId, $search);
                    $result = ['SEARCH' => ['DATA' => $dataSearch]];
                    break;
                case 'bindRatio':
                    $attributeId = $this->request['attributeId'];
                    $ratio = $this->request['ratio'];
                    $status = $this->setPropertyRatio($attributeId, $ratio);
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
        $this->iblockCatalogId = $this->getIblockId($this->arParams['ELEMENT_ID']);
        $this->iblockOffersId = HelperIblock::getOfferIblockId($this->iblockCatalogId);
        $this->initConnectionField($this->request['connectionSectionTree'] ?: 0);
        $this->propertyId = $this->request['propertyId'] ?: 0;
    }

    private function getIblockId($elementId): int
    {
        $parameters = [
            'filter' => [
                'ID' => $elementId
            ],
            'select' => ['IBLOCK_ID'],
            'cache' => ['ttl' => 86400]
        ];
        $result = ProductListTable::getList($parameters);
        if ($row = $result->fetch()) {
            return $row['IBLOCK_ID'] ?: 0;
        }

        return 0;
    }

    private function initConnectionField(int $connectionSectionTree): void
    {
        $connection = ConnectionSectionTreeTable::getById($connectionSectionTree)->fetch();
        $this->connectionSectionTree = $connection['ID'] ?: 0;
        $this->categoryId = $connection['CATEGORY_ID'] ?: 0;
    }

    private function setTemplateData(): void
    {
        $this->connectionPropertyValue = $this->getConnectionAttributeValue();
        $this->connectionPropertyRatio = $this->getConnectionAttributeRatio();
        $sectionTreeList = $this->getConnectionSectionTreeList();

        $elementList = $this->connectionSectionTree > 0 ? $this->getAttributeList() : [];

        $this->arResult['DATA_VUE'] = [
            'TREE' => $sectionTreeList,
            'PROPERTY' => $this->getPropertyDefault(),
            'ATTRIBUTE' => [
                'LIST' => $elementList
            ],
            'SELECTED' => [
                'CONNECTION_SECTION_TREE_ID' => $this->connectionSectionTree
            ]
        ];

        $this->arResult['PATH_TO_AJAX'] = $this->getPath() . '/ajax.php';
        $this->arResult['PATH_TO_AJAX_IMPORT'] = $this->getPath() . '/ajax_import.php';
        $this->arResult['SIGNED_PARAMS'] = (new Signer())->sign(
            base64_encode(serialize($this->arParams)),
            'darneo.ozon.export.product.attribute'
        );
    }

    private function getConnectionAttributeValue(): array
    {
        $rows = [];

        $parameters = [
            'filter' => [
                'IBLOCK_ID' => $this->iblockCatalogId,
            ],
            'select' => [
                'ID',
                'ATTRIBUTE_ID',
                'ATTRIBUTE_VALUE_ID',
                'PROPERTY_ID',
                'PROPERTY_ENUM_ID',
                'ATTR_VALUE' => 'ATTRIBUTE_VALUE.VALUE',
                'ATTR_INFO' => 'ATTRIBUTE_VALUE.INFO',
                'ATTR_PICTURE' => 'ATTRIBUTE_VALUE.PICTURE',
            ],
        ];
        $result = ConnectionPropertyValueTable::getList($parameters);
        while ($row = $result->fetch()) {
            $rows[] = $row;
        }

        return $rows;
    }

    private function getConnectionAttributeRatio(): array
    {
        $rows = [];

        $parameters = [
            'filter' => [
                'IBLOCK_ID' => $this->iblockCatalogId,
            ],
            'select' => [
                'ID',
                'ATTRIBUTE_ID',
                'RATIO'
            ],
        ];
        $result = ConnectionPropertyRatioTable::getList($parameters);
        while ($row = $result->fetch()) {
            $rows[$row['ATTRIBUTE_ID']] = $row['RATIO'];
        }

        return $rows;
    }

    private function getConnectionSectionTreeList(): array
    {
        $rows = [];
        $parameters = [
            'filter' => ['IBLOCK_ID' => $this->iblockCatalogId],
            'select' => [
                'ID',
                'CATEGORY_ID',
                'SECTION_ID',
                'SECTION_NAME' => 'SECTION.NAME',
                'SECTION_DEPTH_LEVEL' => 'SECTION.DEPTH_LEVEL',
                'CATEGORY_NAME' => 'CATEGORY.TITLE',
            ],
            'order' => ['SECTION.LEFT_MARGIN' => 'ASC', 'SECTION.SORT' => 'ASC'],
        ];
        $result = ConnectionSectionTreeTable::getList($parameters);
        while ($row = $result->fetch()) {
            if (empty($row['SECTION_ID'])) {
                $row['SECTION_ID'] = 0;
                $row['SECTION_NAME'] = Loc::getMessage('DARNEO_OZON_MODULE_PRODUCT_ATTR_IBLOCK');
            }
            $rows[] = $row;
        }

        return $rows;
    }

    private function getAttributeList(): array
    {
        $propIds = [];
        $result = ConnectionPropCategoryTable::getList(
            ['filter' => ['CATEGORY_ID' => $this->categoryId], 'select' => ['PROPERTY_ID']]
        );
        while ($row = $result->fetch()) {
            $propIds[] = $row['PROPERTY_ID'];
        }

        $rows = [];
        $parameters = [
            'filter' => ['ID' => $propIds],
            'select' => [
                'ID',
                'NAME',
                'TYPE',
                'DICTIONARY_ID',
                'DESCRIPTION',
                'IS_COLLECTION',
                'IS_REQUIRED',
            ],
            'order' => ['IS_REQUIRED' => 'DESC', 'NAME' => 'ASC'],
            'cache' => ['ttl' => 86400]
        ];
        $result = PropertyListTable::getList($parameters);
        while ($row = $result->fetch()) {
            if (in_array((int)$row['ID'], HelperAttribute::STOP_LOAD_ATTRIBUTE, true)) {
                continue;
            }
            $row['DESCRIPTION'] = str_replace('\n', '<br>', $row['DESCRIPTION']);
            $row['IS_RATIO'] = false;
            $row['RATIO'] = $this->connectionPropertyRatio[$row['ID']] ?: 0;
            if ($row['DICTIONARY_ID']) {
                if ($row['IS_COLLECTION']) {
                    $attributeType = HelperAttributeType::LIST_MULTI;
                } else {
                    $attributeType = HelperAttributeType::LIST;
                }
            } else {
                switch ($row['TYPE']) {
                    case 'URL':
                        $attributeType = HelperAttributeType::URL;
                        break;
                    case 'Boolean':
                        $attributeType = HelperAttributeType::BOOLEAN;
                        break;
                    case 'Decimal':
                        $attributeType = HelperAttributeType::DECIMAL;
                        $row['IS_RATIO'] = true;
                        break;
                    case 'Integer':
                        $attributeType = HelperAttributeType::INTEGER;
                        $row['IS_RATIO'] = true;
                        break;
                    case 'multiline':
                        $attributeType = HelperAttributeType::MULTILINE;
                        break;
                    default:
                        $attributeType = HelperAttributeType::STRING;
                        break;
                }
            }

            $row['TYPE_HTML'] = HelperAttributeType::getTypeHTML($attributeType);
            $row['ATTRIBUTE_TYPE'] = $attributeType;
            $row['PROPERTY'] = $this->getConnectionSectionPropertyList($row['ID']);
            $row['IBLOCK_PROPERTY_DATA'] = [];
            if ($row['PROPERTY']) {
                $propertyType = $row['PROPERTY']['PROPERTY_TYPE'];
                $propertyValue = $row['PROPERTY']['PROPERTY_VALUE'] ?: '';
                $propertyText = $row['PROPERTY']['VALUE'] ?: '';
                $row['IBLOCK_PROPERTY_DATA'] = $this->getPropertyEnum($propertyType, $propertyValue);
                foreach ($row['IBLOCK_PROPERTY_DATA']['LIST'] as $key => $item) {
                    $attr = [];
                    foreach ($this->connectionPropertyValue as $connection) {
                        if ((int)$item['PROPERTY_ID'] === (int)$connection['PROPERTY_ID']
                            && (int)$item['ID'] === (int)$connection['PROPERTY_ENUM_ID']) {
                            $attr = $connection;
                            break;
                        }
                    }
                    $row['IBLOCK_PROPERTY_DATA']['LIST'][$key]['ATTR'] = $attr;
                }
                $row['IBLOCK_PROPERTY_DATA']['LIST'] = array_values($row['IBLOCK_PROPERTY_DATA']['LIST']);
            }
            $row['OZON_PROPERTY_DATA'] = $this->getAttributeValue($row['ID']);
            $rows[] = $row;
        }

        return $rows;
    }

    private function getConnectionSectionPropertyList(int $attributeId): array
    {
        $rows = [
            'PROPERTY_TYPE' => '',
            'PROPERTY_VALUE' => '',
            'VALUE' => '',
        ];
        $parameters = [
            'filter' => [
                'CONNECTION_SECTION_TREE_ID' => $this->connectionSectionTree,
                'ATTRIBUTE_ID' => $attributeId,
            ],
            'select' => [
                'PROPERTY_TYPE',
                'PROPERTY_VALUE',
                'VALUE',
            ],
            'cache' => ['ttl' => 86400]
        ];
        $result = ConnectionCategoryPropertyTable::getList($parameters);
        if ($row = $result->fetch()) {
            $rows = $row;
        }

        return $rows;
    }

    private function getPropertyEnum(string $propertyType, string $propertyValue = ''): array
    {
        $rows = [];
        switch ($propertyType) {
            case 'PROP':
                $propertyId = (int)$propertyValue;
                $property = $this->getPropertyById($propertyId);
                switch ($property['PROPERTY_TYPE']) {
                    case PropertyTable::TYPE_LIST:
                        $rows = $this->getPropertyEnumList($propertyId);
                        break;
                    case PropertyTable::TYPE_ELEMENT:
                        $linkIblockId = $property['LINK_IBLOCK_ID'];
                        $rows = $linkIblockId > 0 ? $this->getElementList($linkIblockId, $propertyId) : [];
                        break;
                }

                break;
        }

        return [
            'LIST' => $rows,
            'PAGE' => 1,
            'FINAL_PAGE' => true,
            'FILTER' => [
                'SEARCH' => ''
            ]
        ];
    }

    private function getPropertyById(int $propertyId): array
    {
        $rows = [];

        $parameters = [
            'filter' => [
                'ID' => $propertyId,
                'IBLOCK_ID' => [$this->iblockCatalogId, $this->iblockOffersId],
                'ACTIVE' => 'Y',
                '!=CODE' => 'CML2_LINK'
            ],
            'select' => ['ID', 'CODE', 'NAME', 'PROPERTY_TYPE', 'IBLOCK_ID', 'LINK_IBLOCK_ID'],
            'order' => ['IBLOCK_ID' => 'ASC', 'SORT' => 'ASC', 'NAME' => 'ASC']
        ];
        $result = PropertyTable::getList($parameters);
        if ($row = $result->fetch()) {
            $prefix = (int)$row['IBLOCK_ID'] === $this->iblockOffersId ? 'OFFERS: ' : 'CATALOG: ';
            $rows = [
                'ID' => $row['ID'],
                'CODE' => $row['CODE'],
                'PROPERTY_TYPE' => $row['PROPERTY_TYPE'],
                'NAME' => $prefix . $row['NAME'],
                'LINK_IBLOCK_ID' => $row['LINK_IBLOCK_ID'],
            ];
        }

        return $rows;
    }

    private function getPropertyEnumList(int $propertyId): array
    {
        $rows = [];

        $parameters = [
            'filter' => [
                'PROPERTY_ID' => $propertyId,
            ],
            'select' => ['ID', 'PROPERTY_ID', 'VALUE'],
            'order' => ['SORT' => 'ASC', 'VALUE' => 'ASC'],
        ];
        $result = PropertyEnumerationTable::getList($parameters);
        while ($row = $result->fetch()) {
            $rows[] = [
                'ID' => $row['ID'],
                'PROPERTY_ID' => $row['PROPERTY_ID'],
                'VALUE' => $row['VALUE'],
                'SELECTED' => false,
            ];
        }

        return $rows;
    }

    private function getElementList(int $iblockId, int $propertyId): array
    {
        $rows = [];
        $parameters = [
            'filter' => [
                'IBLOCK_ID' => $iblockId
            ],
            'select' => ['ID', 'NAME'],
            'cache' => ['ttl' => 86400]
        ];
        $result = ElementTable::getList($parameters);
        while ($row = $result->fetch()) {
            $rows[$row['ID']] = [
                'ID' => $row['ID'],
                'PROPERTY_ID' => $propertyId,
                'VALUE' => $row['NAME'],
                'SELECTED' => false,
            ];
        }

        return $rows;
    }

    private function getAttributeValue(int $propertyId): array
    {
        $attrValue = new AttributeValue($this->categoryId, $propertyId);
        if ($this->propertyId === $propertyId) {
            $page = $this->request['page'] ?: 1;
            $searchValue = $this->request['search'] ?: '';
            $attrValue->setFilterSearch($searchValue);
            $attrValue->setPage($page);
        }
        $attr = $attrValue->get();

        foreach ($attr['LIST'] as $key => $item) {
            $selected = '';
            foreach ($this->connectionPropertyValue as $connection) {
                if ($connection['ATTRIBUTE_ID'] === $item['PROPERTY_ID'] && $connection['ATTRIBUTE_VALUE_ID'] === $item['ID']) {
                    $selected = $connection['PROPERTY_ENUM_ID'];
                }
            }
            $attr['LIST'][$key]['ENUM_SELECTED_ID'] = $selected;
        }

        return $attr;
    }

    private function getPropertyDefault(): array
    {
        $property = [];

        $property['GROUP'] = [
            [
                'ID' => 'VALUE',
                'NAME' => Loc::getMessage('DARNEO_OZON_MODULE_PRODUCT_ATTR_GROUP_VALUE'),
            ],
            [
                'ID' => 'ELEMENT',
                'NAME' => Loc::getMessage('DARNEO_OZON_MODULE_PRODUCT_ATTR_GROUP_ELEMENT'),
            ],
            [
                'ID' => 'PROP',
                'NAME' => Loc::getMessage('DARNEO_OZON_MODULE_PRODUCT_ATTR_GROUP_PROP'),
            ],
            [
                'ID' => 'DIMENSION',
                'NAME' => Loc::getMessage('DARNEO_OZON_MODULE_PRODUCT_ATTR_GROUP_DIMENSION'),
            ],
        ];
        $property['ITEMS']['VALUE'] = [];
        $element = [
            [
                'ID' => 'CATALOG_NAME',
                'NAME' => Loc::getMessage('DARNEO_OZON_MODULE_PRODUCT_ATTR_GROUP_ITEMS_ELEMENT_CATALOG_NAME'),
            ],
            [
                'ID' => 'CATALOG_PREVIEW_TEXT',
                'NAME' => Loc::getMessage('DARNEO_OZON_MODULE_PRODUCT_ATTR_GROUP_ITEMS_ELEMENT_CATALOG_PREVIEW_TEXT'),
            ],
            [
                'ID' => 'CATALOG_DETAIL_TEXT',
                'NAME' => Loc::getMessage('DARNEO_OZON_MODULE_PRODUCT_ATTR_GROUP_ITEMS_ELEMENT_CATALOG_DETAIL_TEXT'),
            ],
        ];
        if ($this->iblockOffersId > 0) {
            $element = array_merge($element, [
                [
                    'ID' => 'OFFERS_NAME',
                    'NAME' => Loc::getMessage('DARNEO_OZON_MODULE_PRODUCT_ATTR_GROUP_ITEMS_ELEMENT_OFFERS_NAME'),
                ],
                [
                    'ID' => 'OFFERS_PREVIEW_TEXT',
                    'NAME' => Loc::getMessage(
                        'DARNEO_OZON_MODULE_PRODUCT_ATTR_GROUP_ITEMS_ELEMENT_OFFERS_PREVIEW_TEXT'
                    ),
                ],
                [
                    'ID' => 'OFFERS_DETAIL_TEXT',
                    'NAME' => Loc::getMessage('DARNEO_OZON_MODULE_PRODUCT_ATTR_GROUP_ITEMS_ELEMENT_OFFERS_DETAIL_TEXT'),
                ],
            ]);
        }
        $property['ITEMS']['ELEMENT'] = $element;
        $property['ITEMS']['PROP'] = $this->getPropertyList();
        $dimension = [
            [
                'ID' => 'CATALOG_WEIGHT',
                'NAME' => Loc::getMessage('DARNEO_OZON_MODULE_PRODUCT_ATTR_GROUP_ITEMS_DIMENSION_CATALOG_WEIGHT'),
            ],
            [
                'ID' => 'CATALOG_WIDTH',
                'NAME' => Loc::getMessage('DARNEO_OZON_MODULE_PRODUCT_ATTR_GROUP_ITEMS_DIMENSION_CATALOG_WIDTH'),
            ],
            [
                'ID' => 'CATALOG_HEIGHT',
                'NAME' => Loc::getMessage('DARNEO_OZON_MODULE_PRODUCT_ATTR_GROUP_ITEMS_DIMENSION_CATALOG_HEIGHT'),
            ],
            [
                'ID' => 'CATALOG_LENGTH',
                'NAME' => Loc::getMessage('DARNEO_OZON_MODULE_PRODUCT_ATTR_GROUP_ITEMS_DIMENSION_CATALOG_LENGTH'),
            ],
        ];
        if ($this->iblockOffersId > 0) {
            $dimension = array_merge($dimension, [
                [
                    'ID' => 'OFFERS_WEIGHT',
                    'NAME' => Loc::getMessage('DARNEO_OZON_MODULE_PRODUCT_ATTR_GROUP_ITEMS_DIMENSION_OFFERS_WEIGHT'),
                ],
                [
                    'ID' => 'OFFERS_WIDTH',
                    'NAME' => Loc::getMessage('DARNEO_OZON_MODULE_PRODUCT_ATTR_GROUP_ITEMS_DIMENSION_OFFERS_WIDTH'),
                ],
                [
                    'ID' => 'OFFERS_HEIGHT',
                    'NAME' => Loc::getMessage('DARNEO_OZON_MODULE_PRODUCT_ATTR_GROUP_ITEMS_DIMENSION_OFFERS_HEIGHT'),
                ],
                [
                    'ID' => 'OFFERS_LENGTH',
                    'NAME' => Loc::getMessage('DARNEO_OZON_MODULE_PRODUCT_ATTR_GROUP_ITEMS_DIMENSION_OFFERS_LENGTH'),
                ],
            ]);
        }
        $property['ITEMS']['DIMENSION'] = $dimension;

        return $property;
    }

    private function getPropertyList(): array
    {
        $rows = [];

        $parameters = [
            'filter' => [
                'IBLOCK_ID' => [$this->iblockCatalogId, $this->iblockOffersId],
                'ACTIVE' => 'Y',
                '!=CODE' => 'CML2_LINK'
            ],
            'select' => ['ID', 'CODE', 'NAME', 'PROPERTY_TYPE', 'IBLOCK_ID'],
            'order' => ['IBLOCK_ID' => 'ASC', 'SORT' => 'ASC', 'NAME' => 'ASC']
        ];
        $result = PropertyTable::getList($parameters);
        while ($row = $result->fetch()) {
            $prefix = (int)$row['IBLOCK_ID'] === $this->iblockOffersId ? 'OFFERS: ' : 'CATALOG: ';
            $rows[] = [
                'ID' => $row['ID'],
                'CODE' => $row['CODE'],
                'PROPERTY_TYPE' => $row['PROPERTY_TYPE'],
                'PROPERTY_TYPE_HTML' => HelperPropertyType::getTypeHTML($row['PROPERTY_TYPE']),
                'NAME' => $prefix . $row['NAME'],
                'SELECTED' => false,
            ];
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

    private function setCategoryProperty(
        int $attributeId,
        string $propertyType,
        string $propertyValue = '',
        string $value = ''
    ): array {
        $id = $this->getConnectionId($this->connectionSectionTree, $attributeId);
        $params = [
            'CONNECTION_SECTION_TREE_ID' => $this->connectionSectionTree,
            'ATTRIBUTE_ID' => $attributeId,
            'PROPERTY_TYPE' => $propertyType,
            'PROPERTY_VALUE' => $propertyValue,
            'VALUE' => $value,
        ];

        if ($id) {
            $result = ConnectionCategoryPropertyTable::update($id, $params);
        } else {
            $result = ConnectionCategoryPropertyTable::add($params);
        }
        if ($result->isSuccess()) {
            return ['STATUS' => 'SUCCESS'];
        }

        return ['ERROR_LIST' => $result->getErrorMessages(), 'STATUS' => 'ERROR'];
    }

    private function getConnectionId(int $connectionId, int $attributeId): int
    {
        $parameters = [
            'filter' =>
                [
                    'CONNECTION_SECTION_TREE_ID' => $connectionId,
                    'ATTRIBUTE_ID' => $attributeId
                ],
            'select' => ['ID']
        ];
        $result = ConnectionCategoryPropertyTable::getList($parameters);
        if ($row = $result->fetch()) {
            return $row['ID'];
        }

        return 0;
    }

    private function setConnectionAttributeEnum(
        int $attributeId,
        int $attributeValueId,
        int $propertyId,
        int $propertyEnumId
    ): array {
        $connection = ConnectionPropertyValueTable::getList(
            [
                'filter' =>
                    [
                        'IBLOCK_ID' => $this->iblockCatalogId,
                        'ATTRIBUTE_ID' => $attributeId,
                        'ATTRIBUTE_VALUE_ID' => $attributeValueId,
                    ],
                'select' => ['ID']
            ]
        )->fetch();

        if (empty($propertyId) || empty($propertyEnumId)) {
            $result = ConnectionPropertyValueTable::delete($connection['ID']);
        } else {
            $params = [
                'IBLOCK_ID' => $this->iblockCatalogId,
                'ATTRIBUTE_ID' => $attributeId,
                'ATTRIBUTE_VALUE_ID' => $attributeValueId,
                'PROPERTY_ID' => $propertyId,
                'PROPERTY_ENUM_ID' => $propertyEnumId,
            ];
            if ($connection['ID']) {
                $result = ConnectionPropertyValueTable::update($connection['ID'], $params);
            } else {
                $result = ConnectionPropertyValueTable::add($params);
            }
        }

        if ($result->isSuccess()) {
            return ['STATUS' => 'SUCCESS'];
        }

        return ['ERROR_LIST' => $result->getErrorMessages(), 'STATUS' => 'ERROR'];
    }

    private function searchEnumOzon(int $attributeId, string $text): array
    {
        $rows = [];
        if (empty($text)) {
            return $rows;
        }

        $parameters = [
            'filter' => [
                'PROPERTY_ID' => $attributeId,
                '%VALUE_VALUE' => $text,
            ],
            'select' => [
                'VALUE_ID',
                'VALUE_VALUE' => 'VALUE.VALUE',
                'INFO' => 'VALUE.INFO',
                'PICTURE' => 'VALUE.PICTURE',
            ],
            'order' => [
                'VALUE_VALUE' => 'ASC',
                'ID' => 'DESC'
            ],
            'limit' => 10
        ];

        ConnectionPropValueTable::setTablePrefix($this->categoryId);
        $result = ConnectionPropValueTable::getList($parameters);

        while ($row = $result->fetch()) {
            $rows[] = [
                'ID' => $row['VALUE_ID'],
                'NAME' => trim($row['VALUE_VALUE']),
                'INFO' => mb_strtolower(trim($row['INFO'])),
                'PICTURE' => $row['PICTURE'],
            ];
        }

        return $rows;
    }

    private function setPropertyRatio(int $attributeId, float $ratio): array
    {
        $connection = ConnectionPropertyRatioTable::getList(
            [
                'filter' =>
                    [
                        'IBLOCK_ID' => $this->iblockCatalogId,
                        'ATTRIBUTE_ID' => $attributeId,
                    ],
                'select' => ['ID']
            ]
        )->fetch();

        if ($connection['ID'] && empty($ratio)) {
            $result = ConnectionPropertyRatioTable::delete($connection['ID']);
        } else {
            $params = [
                'IBLOCK_ID' => $this->iblockCatalogId,
                'ATTRIBUTE_ID' => $attributeId,
                'RATIO' => $ratio
            ];
            if ($connection['ID']) {
                $result = ConnectionPropertyRatioTable::update($connection['ID'], $params);
            } else {
                $result = ConnectionPropertyRatioTable::add($params);
            }
        }

        if ($result->isSuccess()) {
            return ['STATUS' => 'SUCCESS'];
        }

        return ['ERROR_LIST' => $result->getErrorMessages(), 'STATUS' => 'ERROR'];
    }

    public function onPrepareComponentParams($arParams): array
    {
        $this->arParams = $arParams;

        return $this->arParams;
    }
}
