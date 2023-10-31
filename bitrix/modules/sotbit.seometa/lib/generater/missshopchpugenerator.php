<?php
namespace Sotbit\Seometa\Generater;

class MissShopChpuGenerator extends Common
{
    protected function GeneratePriceParams($CondValProps)
    {
        $prices = '';
        $FilterParams = '';

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
            $FilterParams .= "price" . $PriceProps['ID'][0] . $ValMin . $ValMax .= "/";
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
        $FilterParams = '';
        $CntCondValProps = count( $CondValProps['MISSSHOP'][1] );
        $values = array ();
        $k = 1;

        foreach ( $CondValProps['MISSSHOP'][1] as $PropVal )
        {
            if ($k == $CntCondValProps)
            {
                $FilterParams .= $PropVal;
                $values[] = $PropVal;
            }
            else
            {
                $FilterParams .= $PropVal . '-or-';
                $values[] = $PropVal;
            }
            ++ $k;
        }

        return array(
            'PROPERTY' => array(
                'VALUES' => array('#PROPERTY_VALUE#' => $values),
                'PARAMS' => mb_strtolower($CondValProps['CODE']).'-'.$FilterParams.'/',
                'SEARCH_PROPERTIES' => array($CondValProps['CODE'] => $CondValProps['ORIGIN_VALUE']),
                'COND_PROPERTIES' => array($CondValProps['CODE'] => $CondValProps['ORIGIN_VALUE']),
            ));
    }
}
?>