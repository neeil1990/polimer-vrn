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

			$shipmentWeight = null;
			$hasAllBoxesWeight = true;

			foreach ($shipment['BOX'] as $box)
			{
				if (!isset($box['WEIGHT']['VALUE']))
				{
					$hasAllBoxesWeight = false;
					continue;
				}

				if ($shipmentWeight === null) { $shipmentWeight = 0; }

				$shipmentWeight += Market\Data\Weight::convertUnit($box['WEIGHT']['VALUE'], $box['WEIGHT']['UNIT'], $weightUnit);
			}

			if (!$hasAllBoxesWeight && isset($shipment['WEIGHT']['VALUE'], $shipment['WEIGHT']['UNIT']))
			{
				$shipmentWeight = Market\Data\Weight::convertUnit($shipment['WEIGHT']['VALUE'], $shipment['WEIGHT']['UNIT'], $weightUnit);
			}

			if ($shipmentWeight !== null)
			{
				if ($weight === null) { $weight = 0; }

				$weight += $shipmentWeight;
			}
		}
	}

	if (isset($item['TOTAL'], $item['SUBSIDY']))
	{
		$item['TOTAL'] += $item['SUBSIDY'];
	}

	$item['CAPACITY'] = $capacity;
	$item['WEIGHT'] = $weight;
}
unset($item);