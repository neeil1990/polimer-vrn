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
?>

<?php
$APPLICATION->IncludeComponent(
    'darneo.ozon_v3:settings.cron.list',
    '',
    [
        'SEF_FOLDER' => $arResult['SEF_FOLDER']
    ]
);
?>
