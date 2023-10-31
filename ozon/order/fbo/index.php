<?

define('NEED_AUTH', true);
require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php');
$APPLICATION->SetTitle('Заказы со склада Ozon (FBO)');
?>
<?
$APPLICATION->IncludeComponent(
    'darneo.ozon:order.fbo',
    '',
    [
        'COMPONENT_TEMPLATE' => '',
        'SEF_URL_TEMPLATES' => [
            'list' => '',
            'kanban' => 'kanban/',
        ],
        'SEF_FOLDER' => '/ozon/order/fbo/',
        'SEF_MODE' => 'Y'
    ]
);
?>
<? require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php'); ?>