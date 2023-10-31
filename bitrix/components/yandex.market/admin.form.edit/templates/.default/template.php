<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) { die(); }

$availableLayouts = [
	'tabs',
	'raw',
];
$layout = isset($arParams['LAYOUT']) && in_array($arParams['LAYOUT'], $availableLayouts, true)
	? $arParams['LAYOUT']
	: reset($availableLayouts);

include __DIR__ . '/partials/layout-' . $layout . '.php';
