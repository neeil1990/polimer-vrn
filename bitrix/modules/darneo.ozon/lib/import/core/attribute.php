<?php

namespace Darneo\Ozon\Import\Core;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Json;
use Darneo\Ozon\Api;
use Darneo\Ozon\Import\Table\ConnectionPropCategoryTable;
use Darneo\Ozon\Import\Table\PropertyGroupTable;
use Darneo\Ozon\Import\Table\PropertyListTable;
use Darneo\Ozon\Import\Table\TreeTable;

class Attribute extends Base
{
    public function start(int $filterCategoryId = 0): void
    {
        $level3 = $this->getLevel3();
        foreach ($level3 as $categoryId) {
            $data = (new Api\v3\Category())->attribute($categoryId);
            if (!$data['result']) {
                $this->errors[] = Loc::getMessage(
                    'DARNEO_OZON_IMPORT_CORE_ATTR_ERROR_IMPORT',
                    [
                        '#CATEGORY_ID#' => $categoryId,
                        '#ANSWER#' => Json::encode($data),
                    ]
                );
                continue;
            }

            $dataArray = array_shift($data['result']);
            $dataArray = $dataArray['attributes'] ?: [];

            foreach ($dataArray as $prop) {
                $propId = $this->addCategoryAttribute($prop);
                if ($propId) {
                    $groupId = 0;
                    if ($prop['group_id'] > 0) {
                        $groupId = $this->addGroup($prop['group_id'], $prop['group_name']);
                    }
                    $this->addConnectionAttribute($propId, $categoryId, $groupId);
                }
            }
        }
    }

    private function getLevel3(int $filterCategoryId = 0): array
    {
        $rows = [];
        $disable = $this->getTreeDisable();
        $parameters = ['select' => ['CATEGORY_ID'], 'filter' => ['LEVEL' => 3, '!=CATEGORY_ID' => $disable]];
        if ($filterCategoryId) {
            $parameters['filter']['CATEGORY_ID'] = $filterCategoryId;
        }
        $result = TreeTable::getList($parameters);
        while ($row = $result->fetch()) {
            $rows[] = $row['CATEGORY_ID'];
        }

        return $rows;
    }

    private function addCategoryAttribute(array $prop): int
    {
        $result = PropertyListTable::add(
            [
                'ID' => $prop['id'],
                'NAME' => $prop['name'],
                'TYPE' => $prop['type'],
                'DICTIONARY_ID' => $prop['dictionary_id'],
                'DESCRIPTION' => $prop['description'],
                'IS_COLLECTION' => $prop['is_collection'],
                'IS_REQUIRED' => $prop['is_required'],
            ]
        );
        if ($result->isSuccess()) {
            return $result->getId();
        }

        $this->errors[] = array_merge($this->errors, $result->getErrorMessages());
        return 0;
    }

    private function addGroup(int $groupId, string $name): int
    {
        $result = PropertyGroupTable::add(
            [
                'ID' => $groupId,
                'NAME' => $name,
            ]
        );
        if ($result->isSuccess()) {
            return $result->getId();
        }

        $this->errors[] = array_merge($this->errors, $result->getErrorMessages());
        return 0;
    }

    private function addConnectionAttribute(int $propId, int $categoryId, int $groupId): void
    {
        $params = [
            'PROPERTY_ID' => $propId,
            'CATEGORY_ID' => $categoryId
        ];
        if ($groupId) {
            $params['GROUP_ID'] = $groupId;
        }

        $result = ConnectionPropCategoryTable::add($params);
        if (!$result->isSuccess()) {
            $this->errors[] = array_merge($this->errors, $result->getErrorMessages());
        }
    }
}
