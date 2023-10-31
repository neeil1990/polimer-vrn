<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) { die(); }

use Yandex\Market;

global $APPLICATION;

/** @var $adminList \CAdminList */

$adminList->bShowActions = true;

ob_start();

foreach ($adminList->aRows as $row)
{
	$row->Display();
}

$rows = ob_get_clean();

Market\Utils\HttpResponse::sendJson([
	'rows' => $rows,
	'navigation' => $adminList->sNavText,
]);
