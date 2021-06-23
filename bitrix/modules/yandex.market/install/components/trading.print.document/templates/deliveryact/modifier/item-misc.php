<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) { die(); }

use Yandex\Market;

$weightUnit = Market\Data\Weight::UNIT_KILOGRAM;

foreach ($arResult['ITEMS'] as &$item)
{
	$capacity = null;
	$weight = null;

	if (!empty($item['SHIPMENT']))
	{
		foreach ($item['SHIPMENT'] as $shipment)
		{
			if (!isset($shipment['BOX'])) { continue; }

			// capacity

			if ($capacity === null) { $capacity = 0; }

			$capacity += count($shipment['BOX']);

			// weight

			foreach ($shipment['BOX'] as $box)
			{
				if (!isset($box['WEIGHT']['VALUE'])) { continue; }

				if ($weight === null) { $weight = 0; }

				$weight += Market\Data\Weight::convertUnit($box['WEIGHT']['VALUE'], $box['WEIGHT']['UNIT'], $weightUnit);
			}
		}
	}

	$item['CAPACITY'] = $capacity;
	$item['WEIGHT'] = $weight;
}
unset($item);