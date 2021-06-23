<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) { die(); }

$pageCapacity = 38;
$firstPageGap = 6;
$lastPageGap = 11;
$arResult['PAGES'] = [];

// split pages

$result = [];
$rowsCount = count($arResult['ITEMS']);
$pageOffset = 0;

while ($pageOffset < $rowsCount)
{
	$pageSize = $pageOffset === 0 ? $pageCapacity - $firstPageGap : $pageCapacity;

	$arResult['PAGES'][] = [
		'ITEMS' => array_slice($arResult['ITEMS'], $pageOffset, $pageSize),
	];

	$pageOffset += $pageSize;
}

// pages count

$arResult['PAGES_COUNT'] = count($arResult['PAGES']);
$arResult['PAGES_TOTAL'] = $arResult['PAGES_COUNT'];

$lastPageCapacity = $pageCapacity - $lastPageGap;
$lastPage = end($arResult['PAGES']);

if (count($arResult['PAGES']) === 1)
{
	$lastPageCapacity -= $firstPageGap;
}

if ($lastPage !== false && count($lastPage['ITEMS']) > $lastPageCapacity)
{
	$arResult['PAGES_TOTAL'] += 1;
}