<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) { die(); }

use Bitrix\Main\Web\Json;

global $APPLICATION;

/** @var $adminList \CAdminList */

$adminList->bShowActions = true;

ob_start();

foreach ($adminList->aRows as $row)
{
	$row->Display();
}

$rows = ob_get_clean();

$APPLICATION->RestartBuffer();

echo Json::encode([
	'rows' => $rows,
	'navigation' => $adminList->sNavText,
]);

define('ADMIN_AJAX_MODE', true);
require $_SERVER['DOCUMENT_ROOT'] . BX_ROOT . '/modules/main/include/epilog_admin_after.php';
die();