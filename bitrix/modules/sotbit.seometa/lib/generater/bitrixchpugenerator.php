<?php
namespace Sotbit\Seometa\Generater;

class BitrixChpuGenerator extends Common
{
    protected function GeneratePriceParams($CondValProps)
    {
        $prices = '';
        $FilterParams = '';
        $cond_properties = array();

        foreach ( $CondValProps as $PriceCode => $PriceProps )
        {
            $ValMin = "";
            $ValMax = "";
            foreach ( $PriceProps['TYPE'] as $j => $Type )
            {
                if ($Type == 'MIN')
                    $ValMin = "-from-" . $PriceProps['VALUE'][$j];
                if ($Type == 'MAX')
                    $ValMax = "-to-" . $PriceProps['VALUE'][$j];
            }
            if(!empty($ValMin))
                $cond_properties[$PriceCode]['FROM'] = mb_substr($ValMin, 1);
            if(!empty($ValMax))
                $cond_properties[$PriceCode]['TO'] = mb_substr($ValMax, 1);

            $prices .= "price" . $ValMin . $ValMax;

            if(mb_strtolower(mb_detect_encoding($PriceCode)) == 'utf-8')
                //$PriceCode = \CUtil::translit($PriceCode, 'ru', array("replace_space" => "-", "replace_other" => "-"));
                $PriceCode = rawurlencode(mb_strtolower($PriceCode));

            $FilterParams .= "price-" . $PriceCode . $ValMin . $ValMax .= "/";
        }

        return array('PRICE' => array(
            'PRICES' => $prices,
            'PARAMS' => $FilterParams,
            'SEARCH_PROPERTIES' => array('PRICE' => $cond_properties),
            'COND_PROPERTIES' => $cond_properties
        ));
    }

    protected function GenerateFilterParams($CondValProps)
    {
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

    protected function GenerateParams($CondValProps)
    {
        $FilterParams = array();
        $values = array();
        $key = mb_strtolower($CondValProps['CODE']) . '-is-';
//        $prop = \CIBlockProperty::GetByID(intval($CondValProps['PROPERTY_ID']))->fetch();
        $prop = \Sotbit\Seometa\IblockProperty::getIblockProp(intval($CondValProps['PROPERTY_ID']));


        foreach($CondValProps['BITRIX'][1] as $idProp => $PropVal)
        {
            if(isset($prop['PROPERTY_TYPE']) && $prop['PROPERTY_TYPE'] == "S" && empty($prop['USER_TYPE']))
            {
                $FilterParams[] = $PropVal;

                $values[] = \CUtil::translit(urldecode($PropVal), "ru", array("replace_space" => "-", "replace_other" => "-"));
            }
            else
            {
                $FilterParams[] = $PropVal;

                //$FilterParams[] = $CondValProps['MISSSHOP'][1][$idProp];
                $values[] = $CondValProps['MISSSHOP'][1][$idProp];
            }
        }

        return array(
            'PROPERTY' => array(
                'VALUES' => array('#PROPERTY_VALUE#' => $values),
                'PARAMS' => $key.implode('-or-', $FilterParams).'/',
                'SEARCH_PROPERTIES' => array($CondValProps['PROPERTY_ID'] => $CondValProps['ORIGIN_VALUE']),
                'COND_PROPERTIES' => array($CondValProps['CODE'] => $CondValProps['ORIGIN_VALUE']),
            )
        );
    }
}
?>