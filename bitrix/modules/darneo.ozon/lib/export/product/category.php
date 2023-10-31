<?php

namespace Darneo\Ozon\Export\Product;

use CIBlockElement;
use CIBlockSection;
use Darneo\Ozon\Export\Table\ConnectionSectionTreeTable;

class Category extends Base
{
    public function get(): array
    {
        $tree = $this->getTree();
        $groups = $this->getElementGroup();

        foreach ($groups as $group) {
            $navChain = $this->getElementNavChain($group);
            $reversed = array_reverse($navChain);
            foreach ($reversed as $sectionId) {
                if ($tree[$sectionId]) {
                    return [
                        'SECTION_ID' => $sectionId,
                        'CATEGORY_ID' => $tree[$sectionId],
                    ];
                }
            }
        }
        return [
            'SECTION_ID' => $tree[0] ? 0 : '',
            'CATEGORY_ID' => $tree[0] ?: '',
        ];
    }

    private function getTree(): array
    {
        $rows = [];
        $parameters = [
            'filter' => [
                'IBLOCK_ID' => $this->settings['IBLOCK_ID']
            ],
            'select' => [
                'CATEGORY_ID',
                'SECTION_ID',
            ],
        ];
        $result = ConnectionSectionTreeTable::getList($parameters);
        while ($row = $result->fetch()) {
            $rows[$row['SECTION_ID']] = $row['CATEGORY_ID'];
        }

        return $rows;
    }

    private function getElementGroup(): array
    {
        $rows = [];
        $result = CIBlockElement::GetElementGroups($this->elementId, true);
        while ($row = $result->Fetch()) {
            $rows[] = $row['ID'];
        }
        return $rows;
    }

    private function getElementNavChain(int $sectionId): array
    {
        $rows = [];
        $result = CIBlockSection::GetNavChain($this->settings['IBLOCK_ID'], $sectionId);
        while ($row = $result->GetNext()) {
            $rows[] = $row['ID'];
        }
        return $rows;
    }
}
