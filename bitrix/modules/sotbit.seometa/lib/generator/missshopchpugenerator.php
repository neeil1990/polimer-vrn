<?php
namespace Sotbit\Seometa\Generator;

use Sotbit\Seometa\Generator\AbstractGenerator;
use Sotbit\Seometa\Property\PropertySetEntity;

class MissShopChpuGenerator extends AbstractGenerator
{
    protected $propertyTemplate = 'arrFilter_p_#PROPERTY_ID#-#PROPERTY_VALUE#';

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
        throw new \Exception("the method [" . __METHOD__ . "] didn't implement");
        $filter = '';
        $FilterParams = '';
        $cond_properties = array();

        foreach ( $CondValProps as $PriceCode => $PriceProps )
        {
            $ValMin = "";
            $ValMax = "";
            foreach ( $PriceProps['TYPE'] as $j => $Type )
            {
                if ($Type == 'MIN'){
                    $cond_properties['FILTER'][$PriceCode]['FROM'] = $PriceProps['VALUE'][$j];
                    $ValMin = "-from-" . $PriceProps['VALUE'][$j];
                } elseif ($Type == 'MAX'){
                    $cond_properties['FILTER'][$PriceCode]['TO'] = $PriceProps['VALUE'][$j];
                    $ValMax = "-to-" . $PriceProps['VALUE'][$j];
                }
            }
//            $prop = \CIBlockProperty::GetByID(intval($PriceCode))->fetch();
            $prop = \Sotbit\Seometa\IblockProperty::getIblockProp(intval($PriceCode));
            $filter .= mb_strtolower($prop['CODE']) . $ValMin . $ValMax;
            $FilterParams .= mb_strtolower($prop['CODE']) . $ValMin . $ValMax .= "/";
        }

        return array('FILTER' => array(
            'FILTER' => $filter,
            'PARAMS' => $FilterParams,
            'SEARCH_PROPERTIES' => $cond_properties,
            'COND_PROPERTIES' => $cond_properties['FILTER']
        ));
    }

    protected function generateParams(PropertySetEntity $propertySetEntity)
    {
        if(!$this->mask->hasPropertyFields())
            return;

        $placeholderValue = $this->mask->getPropertyFields();
        foreach($placeholderValue as $propertyHolder => $propertyKey) {
            if($propertyHolder != '#PROPERTY_VALUE#')
                $placeholderValue[$propertyHolder] = mb_strtolower($propertySetEntity->getField($propertyKey));
            else
                $placeholderValue[$propertyHolder] = $propertySetEntity->getField($propertyKey);
        }

        if(isset($placeholderValue['#PROPERTY_VALUE#'])) {
            $placeholderValue['#PROPERTY_VALUE#'] = array_map(function($value) {
                return \CUtil::translit(urldecode($value), "ru", array("replace_space" => $this->mask->getSpaceReplacement(), "replace_other" => "-"));
            },$placeholderValue['#PROPERTY_VALUE#']);
            $placeholderValue['#PROPERTY_VALUE#'] = implode('-or-', $placeholderValue['#PROPERTY_VALUE#']);
        }

        return str_replace(array_keys($placeholderValue), $placeholderValue, $this->propertyTemplate);
    }
}
?>