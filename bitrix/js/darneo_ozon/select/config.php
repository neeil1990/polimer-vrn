<?

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

return [
    'css' => [
        // '/bitrix/js/darneo_ozon/select/css/style.css',
    ],
    'js' => [
        // '/bitrix/js/darneo_ozon/select/js/select2.min.js',
        // '/bitrix/js/darneo_ozon/select/js/lang.js',
        '/bitrix/js/darneo_ozon/select/darneo_ozon.select.js'
    ],
    'lang' => '/bitrix/js/darneo_ozon/select/lang/' . LANGUAGE_ID . '/lang.php',
    'rel' => ['jquery', 'ui.vue']
];
