<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) { die(); }

if (!isset($arResult['BASKET']['COLUMNS']['CIS'])) { return; }

$isMarkingGroupUsed = false;

foreach ($arResult['BASKET']['ITEMS'] as $item)
{
	if (!empty($item['MARKING_GROUP']))
	{
		$isMarkingGroupUsed = true;
		break;
	}
}

if (!$isMarkingGroupUsed)
{
	unset($arResult['BASKET']['COLUMNS']['CIS']);
}