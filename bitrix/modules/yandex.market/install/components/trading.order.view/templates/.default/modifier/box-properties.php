<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) { die(); }

use Bitrix\Main\Localization\Loc;

if (empty($arResult['SHIPMENT'])) { return; }

$properties = [];
$keys = [
	'ID',
	'WEIGHT',
	'SIZE',
];

foreach ($keys as $key)
{
	$property = [
		'NAME' => Loc::getMessage('YANDEX_MARKET_T_TRADING_ORDER_VIEW_BOX_PROPERTY_' . $key),
		'DIMENSIONS' => null,
	];

	if ($key === 'WEIGHT')
	{
		$property['DIMENSIONS'] = [ 'WEIGHT' ];
	}
	else if ($key === 'SIZE')
	{
		$property['DIMENSIONS'] = [ 'WIDTH', 'HEIGHT', 'DEPTH' ];
	}

	if ($property['DIMENSIONS'] !== null)
	{
		foreach ($property['DIMENSIONS'] as $dimensionName)
		{
			if (!isset($arResult['BOX_DIMENSIONS'][$dimensionName])) { continue; }

			$property += array_intersect_key($arResult['BOX_DIMENSIONS'][$dimensionName], [
				'UNIT' => true,
				'UNIT_FORMATTED' => true,
			]);
			break;
		}
	}

	$properties[$key] = $property;
}

$arResult['BOX_PROPERTIES'] = $properties;
