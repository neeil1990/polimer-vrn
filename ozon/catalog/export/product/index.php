<?

define('NEED_AUTH', true);
require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php');
$APPLICATION->SetTitle('Выгрузка и обновление товаров');
?>
<?
$APPLICATION->IncludeComponent(
    'darneo.ozon:export.product',
    '',
    [
        'COMPONENT_TEMPLATE' => '',
        'SEF_URL_TEMPLATES' => [
            'list' => '',
            'detail' => 'detail/#ELEMENT_ID#/',
            'section' => 'section/#ELEMENT_ID#/',
            'attribute' => 'attribute/#ELEMENT_ID#/',
            'export' => 'export/#ELEMENT_ID#/',
        ],
        'SEF_FOLDER' => '/ozon/catalog/export/product/',
        'SEF_MODE' => 'Y'
    ]
);
?>
<? require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php'); ?>