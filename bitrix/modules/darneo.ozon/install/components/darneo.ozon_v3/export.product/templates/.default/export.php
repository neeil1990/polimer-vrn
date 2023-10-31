<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

/**
 * Bitrix vars
 *
 * @global CMain $APPLICATION
 * @global CUser $USER
 * @global CUserTypeManager $USER_FIELD_MANAGER
 * @var array $arParams
 * @var array $arResult
 * @var CBitrixComponentTemplate $this
 * @var $component
 */

$elementId = $arResult['VARIABLES']['ELEMENT_ID'];
$folder = $arResult['SEF_FOLDER'];
?>
<?php $APPLICATION->IncludeComponent(
    'bitrix:menu',
    'tab',
    [
        'ALLOW_MULTI_SELECT' => 'N',
        'CHILD_MENU_TYPE' => 'tab',
        'DELAY' => 'N',
        'MAX_LEVEL' => '1',
        'MENU_CACHE_GET_VARS' => '',
        'MENU_CACHE_TIME' => '36000000',
        'MENU_CACHE_TYPE' => 'A',
        'MENU_CACHE_USE_GROUPS' => 'Y',
        'ROOT_MENU_TYPE' => 'tab',
        'USE_EXT' => 'N',
        'COMPONENT_TEMPLATE' => '.default',
        'ELEMENT_ID' => $elementId,
    ],
    false
); ?>
<?php
$APPLICATION->IncludeComponent(
    'darneo.ozon_v3:export.product.exchange',
    '',
    [
        'SEF_FOLDER' => $folder,
        'ELEMENT_ID' => $elementId,
    ],
    false
);
?>
<?php
$APPLICATION->IncludeComponent(
    'darneo.ozon_v3:export.product.log',
    '',
    [
        'SEF_FOLDER' => $folder,
        'ELEMENT_ID' => $elementId,
    ],
    false
);
?>
