<?php

namespace Darneo\Ozon\Export\Product;

use Bitrix\Catalog\ProductTable;

class Dimension extends Base
{
    public const DIMENSION_UNIT_MM = 'mm';
    public const DIMENSION_UNIT_CM = 'cm';
    public const DIMENSION_UNIT_IN = 'in';
    public const WEIGHT_UNIT_G = 'g';
    public const WEIGHT_UNIT_KG = 'kg';
    public const WEIGHT_UNIT_LB = 'lb';

    public function get(): array
    {
        return $this->getProduct();
    }

    private function getProduct(): array
    {
        $rows = [];

        $parameters = [
            'filter' => [
                'ID' => $this->offerId ?: $this->elementId
            ],
            'select' => [
                'WEIGHT',
                'WIDTH',
                'HEIGHT',
                'LENGTH',
            ]
        ];
        $result = ProductTable::getList($parameters);
        if ($row = $result->fetch()) {
            $rows = [
                'WEIGHT' => (int)$row['WEIGHT'],
                'WIDTH' => (int)$row['WIDTH'],
                'HEIGHT' => (int)$row['HEIGHT'],
                'LENGTH' => (int)$row['LENGTH'],
            ];
        }

        return $rows;
    }
}
