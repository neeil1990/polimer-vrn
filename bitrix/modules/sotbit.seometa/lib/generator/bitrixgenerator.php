<?php
namespace Sotbit\Seometa\Generator;

use Sotbit\Seometa\Property\PropertySetEntity;

class BitrixGenerator extends AbstractGenerator {
    protected $propertyTemplate = 'arrFilter#PROPERTY_CODE#=#PROPERTY_VALUE#';

    protected function generatePriceParams(PropertySetEntity $propertySetEntity)
    {
        if(!$propertySetEntity->isPrice())
            return;

        $priceParam =  "arrFilter_P" . $propertySetEntity->getField('ID');
        $result = $priceParam . '_MIN=';

        if(!is_array($propertySetEntity->getDataField('value'))) {
            $items = [$propertySetEntity->getData()['DATA']];
        } else {
            $items = $propertySetEntity->getDataField('value');
        }
        $maxValue = $minValue = '';

        foreach ($items as $item) {
            if(!$minValue) {
                $minValue = ($item['MINFILTER'] ?: $item['MIN']);
            }

            if(!$maxValue) {
                $maxValue = ($item['MAXFILTER'] ?: $item['MAX']);
            }
        }
        $result .= $minValue ?: '';
        $result .= '&'. $priceParam . '_MAX=';
        $result .= $maxValue ?: '';

        return $result;
    }

    protected function generateFilterParams(PropertySetEntity $propertySetEntity)
    {
        if(!$propertySetEntity->isProperty())
            return;

        $filterParam =  "arrFilter_" . $propertySetEntity->getProperty()->ID;
        $arValues = $propertySetEntity->getField('VALUE');

        if(is_array($arValues) && count($arValues) > 1) {
            sort($arValues);
            return $filterParam . '_MIN=' . (int) $arValues[0] . '&' . $filterParam . '_MAX=' . (int) $arValues[1];
        }

        if(mb_stripos($propertySetEntity->getMeta()[0], 'MIN') !== false) {
            $filterParam .= '_MIN=';
        } else if (mb_stripos($propertySetEntity->getMeta()[0], 'MAX') !== false) {
            $filterParam .= '_MAX=';
        }

        return $filterParam . (int) $propertySetEntity->getProperty()->current()->__get('VALUE');
    }

    protected function generateParams(PropertySetEntity $propertySetEntity)
    {
        if(!$this->mask->hasPropertyFields())
            return;

        $placeholderValue = $this->mask->getPropertyFields();
        foreach($placeholderValue as $propertyHolder => $propertyKey) {
            $placeholderValue[$propertyHolder] = $propertySetEntity->getField($propertyKey);
        }

        $result = implode('&' ,array_map(
            function($code, $value) {
                return 'arrFilter' . $code . '=' . $value;
            },
            $placeholderValue['#PROPERTY_CODE#'],
            $placeholderValue['#PROPERTY_VALUE#']
        ));

        return $result;
    }
}
?>