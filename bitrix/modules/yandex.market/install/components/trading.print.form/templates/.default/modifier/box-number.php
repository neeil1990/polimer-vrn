<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) { die(); }

if (empty($arResult['ITEMS'])) { return; }

$lastBoxNumbers = [];

foreach ($arResult['ITEMS'] as &$item)
{
	if (!isset($item['ORDER_ID'])) { continue; }

	$orderId = $item['ORDER_ID'];
	$boxNumber = isset($lastBoxNumbers[$orderId]) ? $lastBoxNumbers[$orderId] + 1 : 1;

	$item['NUMBER'] = $boxNumber;
	$lastBoxNumbers[$orderId] = $boxNumber;
}
unset($item);