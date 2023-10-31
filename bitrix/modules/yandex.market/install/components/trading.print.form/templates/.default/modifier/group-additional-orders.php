<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) { die(); }

if (empty($arResult['ADDITIONAL_ITEMS'])) { return; }

$supportedGroups = array(
	'READY_TO_SHIP' => 100,
	'SHIPPED' => 600,
);

foreach ($arResult['ADDITIONAL_ITEMS'] as &$item)
{
	$itemGroup = 'DEFAULT';

	if (isset($supportedGroups[$item['SUBSTATUS']]))
	{
		$itemGroup = $item['SUBSTATUS'];
	}

	$item['GROUP'] = $itemGroup;
}
unset($item);

uasort($arResult['ADDITIONAL_ITEMS'], static function($itemA, $itemB) use ($supportedGroups) {
	$aSort = isset($supportedGroups[$itemA['GROUP']]) ? $supportedGroups[$itemA['GROUP']] : 500;
	$bSort = isset($supportedGroups[$itemB['GROUP']]) ? $supportedGroups[$itemB['GROUP']] : 500;

	if ($aSort === $bSort)
	{
		return ($itemA['ID'] > $itemB['ID'] ? -1 : 1);
	}

	return ($aSort < $bSort ? -1 : 1);
});