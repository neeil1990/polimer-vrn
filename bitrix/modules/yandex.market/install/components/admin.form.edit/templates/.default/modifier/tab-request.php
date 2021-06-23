<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) { die(); }

if ($arParams['FORM_BEHAVIOR'] === 'steps')
{
	foreach ($arResult['TABS'] as $tab)
	{
		if ($tab['STEP'] === $arResult['STEP'])
		{
			$_REQUEST[$arParams['FORM_ID'] . '_active_tab'] = $tab['DIV'];
			break;
		}
	}
}