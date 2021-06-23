<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) { die(); }

$sumFields = [
	'TOTAL' => 0,
	'WEIGHT' => 0,
	'CAPACITY' => 0,
];

foreach ($arResult['ITEMS'] as $item)
{
	foreach ($sumFields as $key => $value)
	{
		if (!isset($item[$key])) { continue; }

		$sumFields[$key] += $item[$key];
	}
}

$arResult['TOTAL'] = $sumFields;