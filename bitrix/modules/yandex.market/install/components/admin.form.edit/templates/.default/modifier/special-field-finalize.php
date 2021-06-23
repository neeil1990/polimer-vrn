<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) { die(); }

$arResult['SPECIAL_FIELDS_MAP'] = [];

foreach ($arResult['SPECIAL_FIELDS'] as $specialKey => $fields)
{
	foreach ($fields as $field)
	{
		$arResult['SPECIAL_FIELDS_MAP'][$field] = $specialKey;
	}
}