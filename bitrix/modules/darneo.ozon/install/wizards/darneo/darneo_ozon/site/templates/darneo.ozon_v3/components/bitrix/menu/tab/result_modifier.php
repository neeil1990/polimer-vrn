<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

/**
 * @var array $arParams
 * @var array $arResult
 */

use Bitrix\Main\Application;
use Bitrix\Main\Web\Uri;

$request = Application::getInstance()->getContext()->getRequest();
$uri = new Uri($request->getRequestUri());
$path = $uri->getPath();

foreach ($arResult as $key => $arItem) {
    $arResult[$key]['LINK'] = str_replace('#ID#', $arParams['ELEMENT_ID'], $arItem['LINK']);
    $arResult[$key]['SELECTED'] = $arResult[$key]['LINK'] === $path;
}