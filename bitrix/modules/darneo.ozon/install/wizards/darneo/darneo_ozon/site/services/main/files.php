<?

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

if (!defined('WIZARD_SITE_ID') && !defined('WIZARD_SITE_DIR')) {
    return;
}

CopyDirFiles(
    WIZARD_ABSOLUTE_PATH . '/site/public/ru',
    WIZARD_SITE_PATH,
    $rewrite = true,
    $recursive = true,
    $delete_after_copy = false
);

$arUrlRewrite = [];
if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/urlrewrite.php')) {
    include $_SERVER['DOCUMENT_ROOT'] . '/urlrewrite.php';
}

$arNewUrlRewrite = [
    [
        'CONDITION' => '#^' . WIZARD_SITE_DIR . 'app/fbo/#',
        'RULE' => '',
        'ID' => 'darneo.ozon_v3:order.fbo',
        'PATH' => WIZARD_SITE_DIR . 'app/fbo/index.php',
    ],
    [
        'CONDITION' => '#^' . WIZARD_SITE_DIR . 'app/fbs/#',
        'RULE' => '',
        'ID' => 'darneo.ozon_v3:order.fbs',
        'PATH' => WIZARD_SITE_DIR . 'app/fbs/index.php',
    ],
    [
        'CONDITION' => '#^' . WIZARD_SITE_DIR . 'export/product/#',
        'RULE' => '',
        'ID' => 'darneo.ozon_v3:export.product',
        'PATH' => WIZARD_SITE_DIR . 'export/product/index.php',
    ],
    [
        'CONDITION' => '#^' . WIZARD_SITE_DIR . 'export/price/#',
        'RULE' => '',
        'ID' => 'darneo.ozon_v3:export.price',
        'PATH' => WIZARD_SITE_DIR . 'export/price/index.php',
    ],
    [
        'CONDITION' => '#^' . WIZARD_SITE_DIR . 'export/stock/#',
        'RULE' => '',
        'ID' => 'darneo.ozon_v3:export.stock',
        'PATH' => WIZARD_SITE_DIR . 'export/stock/index.php',
    ],
];

foreach ($arNewUrlRewrite as $arUrl) {
    if (!in_array($arUrl, $arUrlRewrite, true)) {
        CUrlRewriter::Add($arUrl);
    }
}

WizardServices::PatchHtaccess(WIZARD_SITE_PATH);
WizardServices::ReplaceMacrosRecursive(WIZARD_SITE_PATH, ['SITE_DIR' => WIZARD_SITE_DIR]);
