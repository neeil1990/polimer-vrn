<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) { die(); }

/** @var array $arResult */
/** @var \CBitrixComponent $component */

if (isset($arParams['DISABLE_REQUIRED_HIGHLIGHT']) && $arParams['DISABLE_REQUIRED_HIGHLIGHT'] === 'Y')
{
	$arResult['DISABLE_REQUIRED_HIGHLIGHT'] = true;
	return;
}

$isAllRequired = true;

foreach ($arResult['TABS'] as $tab)
{
	foreach ($tab['FIELDS'] as $fieldKey)
	{
		$field = $component->getField($fieldKey);
		$isRequired = ($field['MANDATORY'] === 'Y');

		if (!$isRequired)
		{
			$isAllRequired = false;
			break;
		}
	}

	if (!$isAllRequired) { break; }
}

if ($isAllRequired)
{
	$arResult['DISABLE_REQUIRED_HIGHLIGHT'] = true;
}
