<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) { die(); }

/** @var array $arResult */
/** @var CMain $APPLICATION */

if (isset($_POST['ajax']) && $_POST['ajax'] === 'Y' && $arResult['CONTENT_TYPE'] !== 'text/html')
{
	$APPLICATION->RestartBuffer();
	while (ob_get_level()) { ob_end_clean(); }
	header('Content-type: ' . $arResult['CONTENT_TYPE']);
	echo $arResult['CONTENT_RAW'];
	die();
}

echo $arResult['CONTENT_RAW'];