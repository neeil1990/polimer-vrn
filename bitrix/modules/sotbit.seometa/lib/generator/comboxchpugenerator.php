<?php
namespace Sotbit\Seometa\Generator;

use Sotbit\Seometa\Property\PropertySetEntity;

class ComboxChpuGenerator extends AbstractGenerator {
    protected $propertyTemplate = '#PROPERTY_CODE#-#PROPERTY_VALUE#';

    protected function generatePriceParams(PropertySetEntity $propertySetEntity)
    {
        if(!$propertySetEntity->isPrice())
            return;

        $result =  "price-" . $propertySetEntity->getField('CODE');

        $tmpRes = [];
        if(!is_array($propertySetEntity->getDataField('value'))) {
            $items = [$propertySetEntity->getData()['DATA']];
        } else {
            $items = $propertySetEntity->getDataField('value');
        }

        foreach ($items as $item) {
            $minValue = $item['MINFILTER'];
            if(!$minValue)
                $minValue = $item['MIN'];

            if($minValue)
                $tmpRes[0] = '-from-' . $minValue;

            $maxValue = $item['MAXFILTER'];
            if(!$maxValue)
                $maxValue = $item['MAX'];

            if($maxValue)
                $tmpRes[1] = '-to-' . $maxValue;
        }
        ksort($tmpRes);
        $result .= implode('', $tmpRes);

        return $result;
    }

    protected function generateFilterParams(PropertySetEntity $propertySetEntity)
    {
        if(!$this->mask->hasPropertyFields())
            return;

        $result = mb_strtolower($propertySetEntity->getField('CODE'));
        $arValues = $propertySetEntity->getField('VALUE');

        if(is_array($arValues) && count($arValues) > 1) {
            sort($arValues);
            $result .= '-from-' . (int) $arValues[0] . '-to-' . (int) $arValues[1];
            return $result;
        }

        if(mb_stripos($propertySetEntity->getMeta()[0], 'MIN') !== false) {
            $result .= '-from';
        } else if (mb_stripos($propertySetEntity->getMeta()[0], 'MAX') !== false) {
            $result .= '-to';
        }

        $result .= '-'. (int) $propertySetEntity->getProperty()->current()->__get('VALUE');

        return $result;
    }

    protected function generateParams(PropertySetEntity $propertySetEntity)
    {
        if(!$this->mask->hasPropertyFields())
            return;

        $placeholderValue = $this->mask->getPropertyFields();
        foreach($placeholderValue as $propertyHolder => $propertyKey) {
            $placeholderValue[$propertyHolder] = $propertySetEntity->getField($propertyKey);

            if(!$placeholderValue[$propertyHolder]) {
                $placeholderValue[$propertyHolder] = [$propertySetEntity->getProperty()->current()->__get($propertyKey)];
            }
        }

        if(isset($placeholderValue['#PROPERTY_VALUE#'])) {
            $placeholderValue['#PROPERTY_VALUE#'] = array_map(function($value) {
                return \CUtil::translit(urldecode($value), "ru", array("replace_space" => $this->mask->getSpaceReplacement(), "replace_other" => "_"));
            },$placeholderValue['#PROPERTY_VALUE#']);
            $placeholderValue['#PROPERTY_VALUE#'] = implode('-or-', $placeholderValue['#PROPERTY_VALUE#']);
        }

        return str_replace(array_keys($placeholderValue), $placeholderValue, $this->propertyTemplate);
    }
}
?>