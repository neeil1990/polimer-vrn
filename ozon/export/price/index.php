<?php

const NEED_AUTH = true;
require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php');
$APPLICATION->SetTitle('Цены');
?>
<?php
$APPLICATION->IncludeComponent(
    'darneo.ozon_v3:export.price',
    '',
    [
        'COMPONENT_TEMPLATE' => '',
        'SEF_URL_TEMPLATES' => [
            'list' => '',
            'detail' => 'detail/#ELEMENT_ID#/',
            'export' => 'export/#ELEMENT_ID#/',
            'cron' => 'cron/#ELEMENT_ID#/',
        ],
        'SEF_FOLDER' => '/ozon/export/price/',
        'SETTING_CRON_FOLDER' => '/ozon/settings/cron/',
        'SEF_MODE' => 'Y'
    ]
);
?>
<?php require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php'); ?>