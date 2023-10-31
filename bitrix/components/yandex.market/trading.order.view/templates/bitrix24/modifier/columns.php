<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) { die(); }

$keys = [
	'NOTIFICATION',
	'INFO',
	'DELIVERY',
	'COMMON',
];

$arResult['COLUMNS'] = [];

foreach ($keys as $key)
{
	$arResult['COLUMNS'][$key] = [
		'name' => $key . '_COLUMN',
		'title' => '',
		'type' => 'column',
		'elements' => []
	];
}