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
    'darneo.ozon_v3:alert.cron.analytic',
    '',
    [
        'SETTING_CRON_FOLDER' => $arParams['SETTING_CRON_FOLDER']
    ],
    false
);
?>

<div class='row'>
    <div class='col-12 mb-5'>
        <?php $APPLICATION->IncludeComponent(
            'bitrix:menu',
            'settings_tab',
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
            ],
            false
        ); ?>
    </div>
</div>
<div>
    <?php
    $APPLICATION->IncludeComponent(
        'darneo.ozon_v3:order.fbs.list',
        '',
        [
            'SEF_FOLDER' => $arResult['SEF_FOLDER']
        ],
        false
    );
    ?>
</div>
