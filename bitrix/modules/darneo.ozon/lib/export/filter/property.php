<?php

namespace Darneo\Ozon\Export\Filter;

use Bitrix\Iblock\PropertyEnumerationTable;
use Bitrix\Iblock\PropertyTable;

class Property extends Base
{
    public function get(): array
    {
        $property = $this->getProperty();
        $pef = $this->getPref();

        switch ($property['PROPERTY_TYPE']) {
            case 'N':
            case 'S':
                $value = $this->compareValue;
                $filter = [$pef . 'PROPERTY_' . $property['CODE'] => $value];
                break;
            case 'L':
                $value = $this->compareValue ? $this->getPropertyValue($this->compareValue) : '';
                $filter = [$pef . 'PROPERTY_' . $property['CODE'] . '_VALUE' => $value];
                break;
            case 'E':
                $filter = [$pef . 'PROPERTY_' . $property['CODE'] => (int)$this->compareValue];
                break;
            default:
                $filter = [];
                break;
        }

        return $filter;
    }

    private function getProperty(): array
    {
        $rows = [];
        $parameters = [
            'filter' => [
                'ID' => $this->propertyId
            ],
            'select' => ['ID', 'CODE', 'NAME', 'PROPERTY_TYPE'],
        ];
        $result = PropertyTable::getList($parameters);
        if ($row = $result->fetch()) {
            $rows = $row;
        }

        return $rows;
    }

    private function getPropertyValue(int $propertyId): string
    {
        $rows = '';
        $parameters = [
            'filter' => [
                'ID' => $propertyId
            ],
            'select' => ['VALUE'],
        ];
        $result = PropertyEnumerationTable::getList($parameters);
        if ($row = $result->fetch()) {
            $rows = $row['VALUE'] ?: '';
        }

        return $rows;
    }
}
