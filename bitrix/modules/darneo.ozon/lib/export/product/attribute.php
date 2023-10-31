<?php

namespace Darneo\Ozon\Export\Product;

use CFile;
use CIBlockElement;
use Darneo\Ozon\Export\Table\ConnectionCategoryPropertyTable;
use Darneo\Ozon\Export\Table\ConnectionPropertyRatioTable;
use Darneo\Ozon\Export\Table\ConnectionPropertyValueTable;
use Darneo\Ozon\Export\Table\ConnectionSectionTreeTable;
use Darneo\Ozon\Import\Table\ConnectionPropCategoryTable;
use Darneo\Ozon\Import\Table\PropertyListTable;

class Attribute extends Base
{
    private int $sectionId = 0;
    private int $categoryId = 0;
    private int $connectionSectionTree = 0;
    private array $connectionAttributeRatio = [];

    public function get(): array
    {
        $rows = [];

        $category = (new Category($this->settings['ID'], $this->elementId))->get();
        $this->sectionId = $category['SECTION_ID'] ?: 0;
        $this->categoryId = $category['CATEGORY_ID'] ?: 0;

        if (!$this->categoryId) {
            return $rows;
        }

        $this->connectionSectionTree = $this->getConnectionSectionTreeList();
        if (!$this->connectionSectionTree) {
            return $rows;
        }

        $this->connectionAttributeRatio = $this->getConnectionAttributeRatio();

        $elementMain = (new Element($this->settings['ID'], $this->elementId))->get();
        $elementDimension = (new Dimension($this->settings['ID'], $this->elementId))->get();
        $elementPropertyValue = $this->getElementPropertyValue();

        $offerMain = [];
        $offerDimension = [];
        if ($this->offerId > 0) {
            $offerMain = (new Element($this->settings['ID'], $this->elementId, $this->offerId))->get();
            $offerDimension = (new Dimension($this->settings['ID'], $this->elementId, $this->offerId))->get();
        }

        $attributes = $this->getAttributeList();
        foreach ($attributes as $attribute) {
            $propertyId = $attribute['PROPERTY']['PROPERTY_VALUE'];
            // текущее свойство элемента инфоблока
            $props = $elementPropertyValue[$propertyId];

            $propertyType = $attribute['PROPERTY']['PROPERTY_TYPE'];
            $propertyValue = $attribute['PROPERTY']['PROPERTY_VALUE'];
            $value = $attribute['PROPERTY']['VALUE'];

            $isCatalog = strpos($propertyValue, 'CATALOG_') !== false;
            $isOffer = strpos($propertyValue, 'OFFERS_') !== false;
            $propertyValue = str_replace(['CATALOG_', 'OFFERS_'], '', $propertyValue);

            switch ($propertyType) {
                case 'PROP':
                    if (empty($props)) {
                        break;
                    }
                    if ($attribute['DICTIONARY_ID']) {
                        $connection = $this->getConnectionAttributeValue($attribute['ID'], $propertyId);

                        foreach ($connection as $item) {
                            foreach ($props as $prop) {
                                if ($item['PROPERTY_ENUM_ID'] === $prop['VALUE']) {
                                    if ($rows[$attribute['ID']]) {
                                        $rows[$attribute['ID']]['values'] = array_merge(
                                            $rows[$attribute['ID']]['values'],
                                            [['dictionary_value_id' => (int)$item['ATTRIBUTE_VALUE_ID']]]
                                        );
                                    } else {
                                        $rows[$attribute['ID']] = [
                                            'complex_id' => 0,
                                            'id' => $attribute['ID'],
                                            'values' => [
                                                ['dictionary_value_id' => (int)$item['ATTRIBUTE_VALUE_ID']]
                                            ]
                                        ];
                                    }

                                    $values = $rows[$attribute['ID']]['values'];
                                    // Получаем массив только с значениями "dictionary_value_id"
                                    $valueIds = array_column($values, 'dictionary_value_id');

                                    // Удаляем дубликаты
                                    $uniqueValueIds = array_unique($valueIds);

                                    // Получаем массив, содержащий только уникальные значения
                                    $uniqueValues = [];
                                    foreach ($uniqueValueIds as $id) {
                                        $uniqueValues[] = ['dictionary_value_id' => $id];
                                    }

                                    $rows[$attribute['ID']]['values'] = $uniqueValues;
                                }
                            }
                        }
                    } else {
                        $value = [];
                        foreach ($props as $prop) {
                            if ((int)$propertyId === (int)$prop['ID']) {
                                switch ($prop['PROPERTY_TYPE']) {
                                    case 'F':
                                        $file = '';
                                        if ($prop['VALUE']) {
                                            $file = CFile::GetPath($prop['VALUE']) ?: '';
                                            if ($file) {
                                                if (strpos($file, 'http') === false) {
                                                    $file = $this->settings['DOMAIN'] . $file;
                                                }
                                            }
                                        }
                                        $value[] = $file;
                                        break;
                                    default:
                                        $value[] = $prop['VALUE_ENUM'] ?: $prop['VALUE'];
                                        break;
                                }
                            }
                        }

                        foreach ($value as $key => $item) {
                            if ($item && is_string($item) && $this->isSerializable($item)) {
                                $tmpRow = unserialize($item, ['allowed_classes' => false]);
                                if ($tmpRow['TEXT']) {
                                    $value[$key] = $this->modValue($attribute['ID'], $tmpRow['TEXT']);
                                } else {
                                    $value[$key] = $this->modValue($attribute['ID'], $tmpRow);
                                }
                            } elseif (is_array($item) && $item['TEXT']) {
                                $value[$key] = $this->modValue($attribute['ID'], $item['TEXT']);
                            } else {
                                $value[$key] = $this->modValue($attribute['ID'], $item);
                            }
                        }

                        $value = implode(', ', $value);
                        $rows[$attribute['ID']] = [
                            'complex_id' => 0,
                            'id' => $attribute['ID'],
                            'values' => [
                                ['value' => $value ?: '']
                            ]
                        ];
                    }
                    break;
                case 'DIMENSION':
                    $value = $isCatalog ? $elementDimension[$propertyValue] : $offerDimension[$propertyValue];
                    $value = $value ?: '';
                    $rows[$attribute['ID']] = [
                        'complex_id' => 0,
                        'id' => $attribute['ID'],
                        'values' => [
                            ['value' => (string)$this->modValue($attribute['ID'], $value) ?: '']
                        ]
                    ];
                    break;
                case 'ELEMENT':
                    if (!$isCatalog && !$isOffer) {
                        break;
                    }
                    $value = $isCatalog ? $elementMain[$propertyValue] : $offerMain[$propertyValue];
                    $value = $value ?: '';
                    $rows[$attribute['ID']] = [
                        'complex_id' => 0,
                        'id' => $attribute['ID'],
                        'values' => [
                            ['value' => (string)$this->modValue($attribute['ID'], $value)]
                        ]
                    ];
                    break;
                case 'VALUE':
                    $rows[$attribute['ID']] = [
                        'complex_id' => 0,
                        'id' => $attribute['ID'],
                        'values' => [
                            ['value' => (string)$this->modValue($attribute['ID'], $value)]
                        ]
                    ];
                    break;
            }
        }

        return array_values($rows);
    }

    private function getConnectionSectionTreeList(): int
    {
        $parameters = [
            'filter' => [
                'IBLOCK_ID' => $this->settings['IBLOCK_ID'],
                'CATEGORY_ID' => $this->categoryId,
                'SECTION_ID' => $this->sectionId,
            ],
            'select' => ['ID']
        ];
        $result = ConnectionSectionTreeTable::getList($parameters);
        if ($row = $result->fetch()) {
            return $row['ID'];
        }

        return 0;
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

    private function getElementPropertyValue(): array
    {
        $rows = [];
        $result = CIBlockElement::GetProperty($this->iblockCatalogId, $this->elementId);
        while ($row = $result->Fetch()) {
            if ($row['VALUE']) {
                $rows[$row['ID']][] = $row;
            }
        }

        if ($this->offerId) {
            $result = CIBlockElement::GetProperty($this->iblockOffersId, $this->offerId);
            while ($row = $result->Fetch()) {
                if ($row['VALUE']) {
                    $rows[$row['ID']][] = $row;
                }
            }
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
                'IS_COLLECTION',
                'IS_REQUIRED',
            ],
            'order' => ['IS_REQUIRED' => 'DESC', 'ID' => 'ASC']
        ];
        $result = PropertyListTable::getList($parameters);
        while ($row = $result->fetch()) {
            $row['PROPERTY'] = $this->getConnectionSectionPropertyList($row['ID']);
            if ($row['PROPERTY']) {
                $rows[] = $row;
            }
        }

        return $rows;
    }

    private function getConnectionSectionPropertyList(int $attributeId): array
    {
        $rows = [];
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
        ];
        $result = ConnectionCategoryPropertyTable::getList($parameters);
        if ($row = $result->fetch()) {
            $rows = $row;
        }

        return $rows;
    }

    private function getConnectionAttributeValue($attributeId, $propertyId): array
    {
        $rows = [];
        $parameters = [
            'filter' => [
                'IBLOCK_ID' => $this->settings['IBLOCK_ID'],
                'ATTRIBUTE_ID' => $attributeId,
                'PROPERTY_ID' => $propertyId,
            ],
            'select' => [
                'ATTRIBUTE_ID',
                'ATTRIBUTE_VALUE_ID',
                'PROPERTY_ID',
                'PROPERTY_ENUM_ID'
            ],
        ];
        $result = ConnectionPropertyValueTable::getList($parameters);
        while ($row = $result->fetch()) {
            $rows[] = $row;
        }

        return $rows;
    }

    private function isSerializable(string $value): bool
    {
        $data = unserialize($value, ['allowed_classes' => false]);

        return $data !== false;
    }

    private function modValue(int $attributeId, $value)
    {
        $ratio = $this->connectionAttributeRatio[$attributeId];
        if ($ratio) {
            $value = (float)$value;
            $value *= $ratio;
        }

        return $value;
    }
}
