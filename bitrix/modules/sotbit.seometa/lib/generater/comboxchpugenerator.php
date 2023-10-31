<?php
namespace Sotbit\Seometa\Generater;

class ComboxChpuGenerator extends Common
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
        $FilterParams = array();
        $values = array();
        $key = mb_strtolower( $CondValProps['CODE'] ) . '-';

        foreach ( $CondValProps['MISSSHOP'][1] as $PropVal )
        {
            if($PropVal==='' && isset( $CondValProps['VALUE'] ) && ! is_null( $CondValProps['VALUE'] ))
                $PropVal = $CondValProps['VALUE'];

            $FilterParams[] = $PropVal;
            $values[] = $PropVal;
        }

        return array(
            'PROPERTY' => array(
                'VALUES' => array('#PROPERTY_VALUE#' => $values),
                'PARAMS' => $key.implode('-or-', $FilterParams).'/',
                'SEARCH_PROPERTIES' => array($CondValProps['PROPERTY_ID'] => $CondValProps['ORIGIN_VALUE']),
                'COND_PROPERTIES' => array($CondValProps['CODE'] => $CondValProps['ORIGIN_VALUE']),
            ));
    }
}
?>