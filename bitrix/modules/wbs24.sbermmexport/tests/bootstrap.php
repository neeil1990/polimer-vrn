<?php
use Bitrix\Main\Loader;

$moduleId = basename(dirname(__DIR__, 1));

// подключение ядра 1С-Битрикс
define ('NOT_CHECK_PERMISSIONS', true);
define ('NO_AGENT_CHECK', true);
$GLOBALS['DBType'] = 'mysql';
$_SERVER['DOCUMENT_ROOT'] = realpath(__DIR__ . '/../../../..');
require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php');

// искуственная авторизация в роли админа
$_SESSION['SESS_AUTH']['USER_ID'] = 1;

// подключение автозаргрузки Composer
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/' . $moduleId . '/vendor/autoload.php';

Loader::includeModule($moduleId);

require_once 'BitrixTestCase.php';
