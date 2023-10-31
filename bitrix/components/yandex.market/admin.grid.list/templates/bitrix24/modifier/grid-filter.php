<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) { die(); }

/** @var array $arResult */

$arResult['GRID_PARAMETERS'] += [
	'RENDER_FILTER_INTO_VIEW' => 'inside_pagetitle', // render by old behavior
	'DISABLE_NAVIGATION_BAR' => 'Y',
	'FILTER' => $arResult['FILTER'],
];