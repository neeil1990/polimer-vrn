<?php

namespace Darneo\Ozon\Import\Core;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Json;
use Darneo\Ozon\Api;
use Darneo\Ozon\Import\Table\TreeTable;

class Category extends Base
{
    private array $category = [];

    public function start(): void
    {
        $data = (new Api\v2\Category())->tree();
        if (!$data['result']) {
            $this->errors[] = Loc::getMessage(
                'DARNEO_OZON_IMPORT_CORE_CATEGORY_ERROR_IMPORT',
                [
                    '#ANSWER#' => Json::encode($data),
                ]
            );
            return;
        }

        $this->getGroupsRecursive($data['result']);
        foreach ($this->category as $item) {
            TreeTable::add($item);
        }
    }

    private function getGroupsRecursive(array $row, int $parentId = 0): void
    {
        foreach ($row as $item) {
            $params = [
                'CATEGORY_ID' => $item['category_id'],
                'TITLE' => $item['title'],
                'PARENT_ID' => $parentId ?: '',
            ];
            $this->category[] = $params;
            if ($item['children']) {
                $this->getGroupsRecursive($item['children'], $item['category_id']);
            }
        }
    }
}
