<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) { die(); }

use Yandex\Market;

$arResult['SPECIAL_FIELDS']['external-id'] = [
    'EXTERNAL_ID',
];

foreach ($arResult['TABS'] as $tab)
{
	if (!is_array($tab['FIELDS']) || !in_array('EXTERNAL_ID', $tab['FIELDS'], true)) { continue; }

	foreach ($tab['FIELDS'] as $fieldName)
	{
		if (Market\Data\TextString::getPosition($fieldName, 'EXTERNAL_SETTINGS') === 0)
		{
			$arResult['SPECIAL_FIELDS']['external-id'][] = $fieldName;
		}
	}
}