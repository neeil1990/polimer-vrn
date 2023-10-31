<?php

namespace Darneo\Ozon\Import\Core;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Json;
use Darneo\Ozon\Api;
use Darneo\Ozon\Import\ConnectionPropValue;
use Darneo\Ozon\Import\Helper\Attribute as HelperAttribute;
use Darneo\Ozon\Import\Table\ConnectionPropCategoryTable;
use Darneo\Ozon\Import\Table\PropertyListTable;
use Darneo\Ozon\Import\Table\PropertyValueTable;

class AttributeValue extends Base
{
    public function start(int $filterCategoryId = 0): void
    {
        $propertyCategory = $this->getConnectionCategory($filterCategoryId);
        $attributes = $this->getAttribute();
        foreach ($attributes as $attributeId) {
            if (in_array((int)$attributeId, HelperAttribute::STOP_LOAD_ATTRIBUTE, true)) {
                continue;
            }
            foreach ($propertyCategory[$attributeId] as $categoryId) {
                $lastValue = 0;
                updateValue:
                $data = (new Api\v2\Category())->attributeValues($attributeId, $categoryId, $lastValue);
                if (!$data['result']) {
                    $this->errors[] = Loc::getMessage(
                        'DARNEO_OZON_IMPORT_CORE_ATTR_VALUE_ERROR_IMPORT',
                        [
                            '#ATTRIBUTE_ID#' => $attributeId,
                            '#CATEGORY_ID#' => $categoryId,
                            '#LAST_VALUE_ID#' => $lastValue,
                            '#ANSWER#' => Json::encode($data),
                        ]
                    );
                    continue;
                }

                foreach ($data['result'] as $propValue) {
                    $lastValue = $propValue['id'];
                    if (!PropertyValueTable::getById($propValue['id'])->fetch()) {
                        $result = PropertyValueTable::add(
                            [
                                'ID' => $propValue['id'],
                                'VALUE' => $propValue['value'],
                                'INFO' => $propValue['info'],
                                'PICTURE' => $propValue['picture']
                            ]
                        );
                        if (!$result->isSuccess()) {
                            $this->errors[] = array_merge($this->errors, $result->getErrorMessages());
                        }
                    }

                    $rowId = (new ConnectionPropValue($categoryId))->add(
                        [
                            'VALUE_ID' => $propValue['id'],
                            'PROPERTY_ID' => $attributeId,
                        ]
                    );

                    if (!$rowId) {
                        $this->errors[] = array_merge(
                            $this->errors,
                            ['Error: ' . 'FILE: ' . __FILE__ . ' LINE: ' . __LINE__]
                        );
                    }
                }
                if ($data['has_next'] && $lastValue) {
                    goto updateValue;
                }
            }
        }
    }

    private function getConnectionCategory(int $filterCategoryId = 0): array
    {
        $rows = [];

        $disable = $this->getTreeDisable();
        $parameters = [
            'filter' => ['!=CATEGORY_ID' => $disable],
            'select' => ['CATEGORY_ID', 'PROPERTY_ID'],
            'cache' => ['ttl' => 86400]
        ];
        if ($filterCategoryId) {
            $parameters['filter']['CATEGORY_ID'] = $filterCategoryId;
        }
        $result = ConnectionPropCategoryTable::getList($parameters);
        while ($row = $result->fetch()) {
            $rows[$row['PROPERTY_ID']][] = $row['CATEGORY_ID'];
        }

        return $rows;
    }

    private function getAttribute(): array
    {
        $rows = [];
        $parameters = [
            'select' => ['ID'],
            'filter' => ['>DICTIONARY_ID' => 0],
            'cache' => ['ttl' => 86400]
        ];
        $result = PropertyListTable::getList($parameters);
        while ($row = $result->fetch()) {
            $rows[] = $row['ID'];
        }

        return $rows;
    }
}
