<?php

foreach($arResult['ITEMS'] as $arItem){
    $letter = (substr(trim($arItem['NAME']),0, 1)) ?: "*";
    $arResult['SECTION_ITEMS'][strtoupper($letter)][] = $arItem;
}

if(count($arResult['SECTION_ITEMS']) > 0)
    $arResult['FILTER_LITTER'] = array_keys($arResult['SECTION_ITEMS']);

foreach ($arResult['FILTER_LITTER'] as $k => $let){
    if(is_numeric($let))
        $arResult['FILTER_LITTER']['int'][] = $let;
    elseif (preg_match('/[A-za-z]/u', $let))
        $arResult['FILTER_LITTER']['str_eng'][] = $let;
    else
        $arResult['FILTER_LITTER']['str_rus'][] = $let;

    unset($arResult['FILTER_LITTER'][$k]);
}

