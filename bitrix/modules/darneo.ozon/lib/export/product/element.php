<?php

namespace Darneo\Ozon\Export\Product;

use Bitrix\Iblock\ElementTable;

class Element extends Base
{
    public function get(): array
    {
        return $this->getElement();
    }

    private function getElement(): array
    {
        $rows = [];

        $parameters = [
            'filter' => [
                'ID' => $this->offerId ?: $this->elementId
            ],
            'select' => [
                'NAME',
                'PREVIEW_TEXT',
                'DETAIL_TEXT',
            ]
        ];
        $result = ElementTable::getList($parameters);
        if ($row = $result->fetch()) {
            $rows = $row;
        }

        return $rows;
    }
}