<?php

const NEED_AUTH = true;
require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php');
$APPLICATION->SetTitle('Товары');
?>
<?php
$APPLICATION->IncludeComponent(
    'darneo.ozon_v3:export.product',
    '',
    [
        'COMPONENT_TEMPLATE' => '',
        'SEF_URL_TEMPLATES' => [
            'list' => '',
            'detail' => 'detail/#ELEMENT_ID#/',
            'section' => 'section/#ELEMENT_ID#/',
            'attribute' => 'attribute/#ELEMENT_ID#/',
            'export' => 'export/#ELEMENT_ID#/',
            'cron' => 'cron/#ELEMENT_ID#/',
        ],
        'SEF_FOLDER' => '#SITE_DIR#export/product/',
        'SETTING_CRON_FOLDER' => '#SITE_DIR#settings/cron/',
        'SEF_MODE' => 'Y'
    ]
);
?>
<?php require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php'); ?>