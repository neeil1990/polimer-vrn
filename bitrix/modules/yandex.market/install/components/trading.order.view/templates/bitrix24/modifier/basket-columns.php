<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) { die(); }

if (empty($arResult['BASKET']['COLUMNS'])) { return; }

$sortMap = [
	'INDEX' => 0,
	'NAME' => 1,
	'COUNT' => 8,
	'SUBSIDY' => 9,
	'PRICE' => 10,
	'DELETE' => 20,
];

uksort($arResult['BASKET']['COLUMNS'], static function($aKey, $bKey) use ($sortMap) {
	$aSort = isset($sortMap[$aKey]) ? $sortMap[$aKey] : 5;
	$bSort = isset($sortMap[$bKey]) ? $sortMap[$bKey] : 5;

	if ($aSort === $bSort) { return 0; }

	return $aSort < $bSort ? -1 : 1;
});