<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) { die(); }

$availableSizes = [
	'small',
	'big',
];

if (isset($arParams['SIZE']) && in_array($arParams['SIZE'], $availableSizes, true))
{
	$arResult['SIZE'] = $arParams['SIZE'];
}
else
{
	$arResult['SIZE'] = reset($availableSizes);
}