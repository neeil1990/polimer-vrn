<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) { die(); }

use Bitrix\Main\Localization\Loc;

$sections = [
	'PROPERTIES' => 'INFO',
	'DELIVERY' => 'DELIVERY',
	'COURIER' => 'DELIVERY',
	'BUYER' => 'INFO',
];
$hasActivity = false;

foreach ($sections as $section => $columnKey)
{
	if (empty($arResult[$section])) { continue; }

	$properties = $arResult[$section];
	$configSection = [
		'name' => $section,
		'title' => Loc::getMessage('YANDEX_MARKET_T_TRADING_ORDER_VIEW_' . $section . '_TITLE') ?: $section,
		'type' => 'section',
		'data' => [
			'showButtonPanel' => false,
			'isChangeable' => false,
			'isRemovable' => false,
			'enableToggling' => false, // disable edit
		],
		'elements' => [],
	];

	foreach ($properties as $property)
	{
		if (isset($property['ACTIVITY_ACTION']))
		{
			$hasActivity = true;
		}

		$configSection['elements'][] = [
			'name' => $property['ID'],
		];

		$arResult['EDITOR']['ENTITY_FIELDS'][] = [
			'name' => $property['ID'],
			'title' => html_entity_decode($property['NAME']),
			'type' => 'yamarket_property',
			'editable' => false,
		];

		$property['VALUE'] = html_entity_decode(htmlspecialcharsback($property['VALUE']));

		$arResult['EDITOR']['ENTITY_DATA'][$property['ID']] = $property;
	}

	if (!isset($arResult['COLUMNS'][$columnKey]))
	{
		trigger_error(sprintf('missing %s column', $columnKey), E_USER_WARNING);
		continue;
	}

	$arResult['COLUMNS'][$columnKey]['elements'][] = $configSection;
}

$arResult['JS_MESSAGES']['Property'] = [
	'ACTIVITY_APPLY' => Loc::getMessage('YANDEX_MARKET_T_TRADING_ORDER_VIEW_PROPERTY_ACTIVITY_APPLY'),
];
$arResult['HAS_ACTIVITY'] = $hasActivity;