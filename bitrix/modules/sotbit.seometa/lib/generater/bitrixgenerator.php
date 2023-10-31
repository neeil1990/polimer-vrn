<?php
namespace Sotbit\Seometa\Generater;

class BitrixGenerator extends Common
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
                if ($Type == 'MIN') {
                    $ValMin = "_MIN=" . $PriceProps['VALUE'][$j];
                    $cond_properties[$PriceCode]['FROM'] = $PriceProps['VALUE'][$j];
                }
                if ($Type == 'MAX') {
                    $ValMax = "_MAX=" . $PriceProps['VALUE'][$j];
                    $cond_properties[$PriceCode]['TO'] = $PriceProps['VALUE'][$j];
                }
            }
            if (isset( $ValMin ) && $ValMin != "")
            {
                $FilterParams .= "&arrFilter_P" . $PriceProps['ID'][0] . $ValMin;
            }
            if (isset( $ValMax ) && $ValMax != "")
            {
                $FilterParams .= "&arrFilter_P" . $PriceProps['ID'][0] . $ValMax;
            }

            $prices .= "price" . $ValMin . $ValMax;
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
                    $ValMin = "_MIN=" . $PriceProps['VALUE'][$j];
                } elseif ($Type == 'MAX'){
                    $cond_properties['FILTER'][$PriceCode]['TO'] = $PriceProps['VALUE'][$j];
                    $ValMax = "_MAX=" . $PriceProps['VALUE'][$j];
                }
            }
            if (isset( $ValMin ) && $ValMin != "")
            {
                $FilterParams .= "&arrFilter_" . $PriceProps['ID'][0] . $ValMin;
            }
            if (isset( $ValMax ) && $ValMax != "")
            {
                $FilterParams .= "&arrFilter_" . $PriceProps['ID'][0] . $ValMax;
            }
//            $prop = \CIBlockProperty::GetByID(intval($PriceCode))->fetch();
            $prop = \Sotbit\Seometa\IblockProperty::getIblockProp(intval($PriceCode));
            $filter .= mb_strtolower($prop['CODE']) . $ValMin . $ValMax;
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
        $values = array();

        foreach ( $CondValProps['BITRIX'][0] as $idProp => $PropVal )
        {
            $FilterParams .= '&arrFilter_' . $CondValProps['PROPERTY_ID'] . '_' . mb_strtolower( $PropVal ) . '=Y';
            $values[] = \CUtil::translit($CondValProps['MISSSHOP'][1][$idProp], 'ru', array("replace_space" => "-", "replace_other" => "-"));
        }

        return array(
            'PROPERTY' => array(
                'VALUES' => array('#PROPERTY_VALUE#' => $values),
                'PARAMS' => $FilterParams,
                'SEARCH_PROPERTIES' => array($CondValProps['PROPERTY_ID'] => $CondValProps['ORIGIN_VALUE']),
                'COND_PROPERTIES' => array($CondValProps['CODE'] => $CondValProps['ORIGIN_VALUE']),
            ));
    }
}
?>