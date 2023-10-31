<?php

$_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__, 4);
$DOCUMENT_ROOT = $_SERVER['DOCUMENT_ROOT'];

use Bitrix\Main\Loader;
use Bitrix\Main\Type;
use Darneo\Ozon\Main;
use Darneo\Ozon\Main\Table\SettingsCronTable;

define('STOP_STATISTICS', true);
define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC', 'Y');
define('DisableEventsCheck', true);
define('BX_SECURITY_SHOW_MESSAGE', true);

ignore_user_abort(true);
set_time_limit(0);
ini_set('memory_limit', '2G');

require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/darneo.ozon/settings.php');

$keyId = $argv[1] ?: 0;

DarneoSettingsOzon::setKeyId($keyId);

if (!Loader::includeModule('darneo.ozon')) {
    return;
}

const SETTING_CODE = 'IMPORT_CATALOG';

$settings = SettingsCronTable::getById(SETTING_CODE)->fetch();
if (!$settings['VALUE'] || $settings['IS_STARTED']) {
    if ($settings['IS_STARTED'] && $settings['DATE_START'] instanceof Type\DateTime) {
        if ((new Type\DateTime()) > $settings['DATE_START']->add('60 minutes')) {
            SettingsCronTable::update(SETTING_CODE, ['IS_STARTED' => false]);
        }
    }
    return;
}

$update = SettingsCronTable::update(
    SETTING_CODE,
    ['DATE_START' => new Type\DateTime(), 'DATE_FINISH' => '', 'IS_STARTED' => true]
);
if ($update->isSuccess()) {
    (new Darneo\Ozon\Import\Product\Manager())->start();
}

SettingsCronTable::update(SETTING_CODE, ['DATE_FINISH' => new Type\DateTime(), 'IS_STARTED' => false]);

