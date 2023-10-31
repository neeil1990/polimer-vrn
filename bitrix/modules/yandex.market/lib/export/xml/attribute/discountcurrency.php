<?php

namespace Yandex\Market\Export\Xml\Attribute;

use Yandex\Market;

class DiscountCurrency extends Base
{
    public function getDefaultParameters()
    {
        return [
            'name' => 'currency',
        ];
    }

    public function validate($value, array $context, $siblingsValues = null, Market\Result\XmlNode $nodeResult = null, $settings = null)
    {
        $result = false;

        if (!isset($siblingsValues['unit']) || $siblingsValues['unit'] === DiscountUnit::UNIT_CURRENCY) // is need export
        {
            $result = parent::validate($value, $context, $siblingsValues, $nodeResult, $settings);
        }

        return $result;
    }
}