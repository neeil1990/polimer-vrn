<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) { die(); }

use Bitrix\Main\Web\Json;

/** @var array $arResult */
/** @var CMain $APPLICATION */

if (isset($_POST['ajax']) && $_POST['ajax'] === 'Y')
{
	$APPLICATION->RestartBuffer();
	while (ob_get_level()) { ob_end_clean(); }
	header('Content-type: application/json; charset=' . LANG_CHARSET);
	echo Json::encode([
		'error' => $arResult['ERROR']
	]);
	die();
}

CAdminMessage::ShowMessage([
	'TYPE' => 'ERROR',
	'MESSAGE' => $arResult['ERROR'],
]);