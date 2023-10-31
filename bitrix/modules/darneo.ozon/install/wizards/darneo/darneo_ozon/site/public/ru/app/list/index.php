<?php

const NEED_AUTH = true;
require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php');
$APPLICATION->SetTitle('Список загруженных товаров');
?>
<?php
$APPLICATION->IncludeComponent(
    'darneo.ozon_v3:data.catalog',
    '',
    [
        'COMPONENT_TEMPLATE' => '',
        'SEF_URL_TEMPLATES' => [
            'list' => '',
        ],
        'SEF_FOLDER' => '#SITE_DIR#app/list/',
        'SETTING_CRON_FOLDER' => '#SITE_DIR#settings/cron/',
        'SEF_MODE' => 'Y'
    ]
);
?>
<?php require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php'); ?>