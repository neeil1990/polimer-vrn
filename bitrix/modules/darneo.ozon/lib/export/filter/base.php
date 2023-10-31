<?php

namespace Darneo\Ozon\Export\Filter;

use Darneo\Ozon\Export\Helper\Compare;

class Base
{
    protected int $propertyId;
    protected string $compareType;
    protected string $compareValue;

    public function __construct(int $propertyId, $compareType, $compareValue)
    {
        $this->propertyId = $propertyId;
        $this->compareType = $compareType;
        $this->compareValue = $compareValue;
    }

    protected function getPref(): string
    {
        switch ($this->compareType) {
            case Compare::EQUAL:
                $pref = '=';
                break;
            case Compare::NOT_EQUAL:
                $pref = '!=';
                break;
            case Compare::LIKE:
                $pref = '%';
                break;
            case Compare::NOT_LIKE:
                $pref = '!%';
                break;
            case Compare::EMPTY:
                $pref = '';
                break;
            case Compare::NOT_EMPTY:
                $pref = '!=';
                break;
            case Compare::MORE:
                $pref = '>';
                break;
            case Compare::MORE_OR_EQUAL:
                $pref = '>=';
                break;
            case Compare::LESS:
                $pref = '<';
                break;
            case Compare::LESS_OR_EQUAL:
                $pref = '<=';
                break;
            default:
                $pref = '';
                break;
        }

        return $pref;
    }
}