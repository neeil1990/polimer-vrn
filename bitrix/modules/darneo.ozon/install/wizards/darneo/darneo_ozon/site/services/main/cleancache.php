<?

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Loader;

if (!Loader::includeModule('darneo.ozon')) {
    return;
}

if (!defined('WIZARD_SITE_ID') && !defined('WIZARD_SITE_DIR')) {
    return;
}

Darneo\Ozon\Cache::clean();