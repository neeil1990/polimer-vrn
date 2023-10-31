<?

define('NEED_AUTH', true);
require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php');
$APPLICATION->SetTitle('Список загруженных товаров');
?>
<?
$APPLICATION->IncludeComponent(
    'darneo.ozon:data.catalog',
    '',
    [
        'COMPONENT_TEMPLATE' => '',
        'SEF_URL_TEMPLATES' => [
            'list' => '',
        ],
        'SEF_FOLDER' => '/ozon/catalog/list/',
        'SEF_MODE' => 'Y'
    ]
);
?>
<? require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php'); ?>