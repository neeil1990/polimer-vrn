<?

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

if (!defined('WIZARD_SITE_ID') && !defined('WIZARD_SITE_DIR')) {
    return;
}

$bitrixTemplateDir = $_SERVER['DOCUMENT_ROOT'] . BX_PERSONAL_ROOT . '/templates/';

CopyDirFiles(
    $_SERVER['DOCUMENT_ROOT'] . WizardServices::GetTemplatesPath(WIZARD_RELATIVE_PATH . '/site') . '/',
    $bitrixTemplateDir,
    $rewrite = true,
    $recursive = true,
    $delete_after_copy = false
);
$site = [];
$result = CSite::GetList($by = 'def', $order = 'desc', ['LID' => WIZARD_SITE_ID]);
if ($row = $result->Fetch()) {
    $site = $row;
}

$template = [];
if (!empty($site)) {
    $template[] = [
        'CONDITION' => '',
        'SORT' => 1,
        'TEMPLATE' => 'darneo.ozon_v3'
    ];
    if (count($template) > 0) {
        $arFields = [
            'TEMPLATE' => $template,
            'NAME' => $site['NAME'],
        ];
        $cSite = new CSite();
        $cSite->Update($site['LID'], $arFields);
    }
}

$templateReplaceMacros = [
    'darneo.ozon_v3'
];
foreach ($templateReplaceMacros as $value) {
    $templatePath = $_SERVER['DOCUMENT_ROOT'] . BX_PERSONAL_ROOT . '/templates/' . $value;
    WizardServices::ReplaceMacrosRecursive($templatePath, ['SITE_DIR' => WIZARD_SITE_DIR]);
    WizardServices::ReplaceMacrosRecursive($templatePath, ['SITE_ID' => WIZARD_SITE_ID]);
}
