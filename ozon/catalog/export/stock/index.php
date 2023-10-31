<?

define('NEED_AUTH', true);
require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php');
$APPLICATION->SetTitle('Обновление остатков');
?>
<?
$APPLICATION->IncludeComponent(
    'darneo.ozon:export.stock',
    '',
    [
        'COMPONENT_TEMPLATE' => '',
        'SEF_URL_TEMPLATES' => [
            'list' => '',
            'detail' => 'detail/#ELEMENT_ID#/',
            'export' => 'export/#ELEMENT_ID#/',
            'cron' => 'cron/#ELEMENT_ID#/',
        ],
        'SEF_FOLDER' => '/ozon/catalog/export/stock/',
        'SETTING_CRON_FOLDER' => '/ozon/settings/cron/',
        'SEF_MODE' => 'Y'
    ]
);
?>
<? require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php'); ?>