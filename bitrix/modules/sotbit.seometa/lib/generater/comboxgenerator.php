<?php
namespace Sotbit\Seometa\Generater;

class ComboxGenerator extends Common
{
    protected function GeneratePriceParams($CondValProps)
    {
        return array('PRICE' => array(
            'PRICES' => '',
            'PARAMS' => '',
            'SEARCH_PROPERTIES' => array(),
            'COND_PROPERTIES' => array()
        ));
    }

    protected function GenerateFilterParams($CondValProps)
    {
        return array('FILTER' => array(
            'FILTER' => '',
            'PARAMS' => '',
            'SEARCH_PROPERTIES' => array(),
            'COND_PROPERTIES' => array()
        ));
    }

    protected function GenerateParams($CondValProps)
    {
        $FilterParams = '';
        $values = array ();

        foreach ( $CondValProps['MISSSHOP'][1] as $PropVal )
        {
            if(!empty($FilterParams))
                $FilterParams .= '&';

            if($PropVal==='' && isset( $CondValProps['VALUE'] ) && ! is_null( $CondValProps['VALUE'] ))
                $PropVal = $CondValProps['VALUE'];

            $FilterParams .= mb_strtolower($CondValProps['CODE']) . '=' . mb_strtolower( $PropVal );
            $values[] = $PropVal;
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