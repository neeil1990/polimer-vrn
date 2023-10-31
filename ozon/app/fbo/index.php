<?php

const NEED_AUTH = true;
require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php');
$APPLICATION->SetTitle('Заказы со склада Ozon (FBO)');
?>
<?php
$APPLICATION->IncludeComponent(
    'darneo.ozon_v3:order.fbo',
    '',
    [
        'COMPONENT_TEMPLATE' => '',
        'SEF_URL_TEMPLATES' => [
            'list' => '',
            'kanban' => 'kanban/',
        ],
        'SEF_FOLDER' => '/ozon/app/fbo/',
        'SEF_MODE' => 'Y'
    ]
);
?>
<?php require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php'); ?>