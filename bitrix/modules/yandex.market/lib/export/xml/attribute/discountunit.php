<?php

namespace Yandex\Market\Export\Xml\Attribute;

use Yandex\Market;
use Bitrix\Main;

Main\Localization\Loc::loadMessages(__FILE__);

class DiscountUnit extends Base
{
    const UNIT_CURRENCY = 'currency';
    const UNIT_PERCENT = 'percent';

    public function getDefaultParameters()
    {
        return [
            'name' => 'unit',
        ];
    }

    public function getLangKey()
    {
        return 'EXPORT_ATTRIBUTE_DISCOUNT_UNIT';
    }

    public function validate($value, array $context, $siblingsValues = null, Market\Result\XmlNode $nodeResult = null, $settings = null)
    {
        $result = parent::validate($value, $context, $siblingsValues, $nodeResult, $settings);

        if ($result)
        {
            $isExistsValue = ($value === static::UNIT_CURRENCY || $value === static::UNIT_PERCENT);

            if (!$isExistsValue)
            {
                $result = false;

                if ($nodeResult)
                {
                    $nodeResult->registerError(Market\Config::getLang($this->getLangKey() . '_VALUE_INVALID'));
                }
            }
        }

        return $result;
    }
}