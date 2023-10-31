<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) { die(); }

if (empty($arResult['SHIPMENT']) || empty($arResult['BOX_PROPERTIES'])) { return; }

foreach ($arResult['SHIPMENT'] as &$shipment)
{
	foreach ($shipment['BOX'] as &$box)
	{
		$propertyValues = [];

		foreach ($arResult['BOX_PROPERTIES'] as $propertyName => $propertyDescription)
		{
			$propertyValue = null;

			if (isset($propertyDescription['DIMENSIONS']))
			{
				$dimensionValues = [];

				foreach ($propertyDescription['DIMENSIONS'] as $dimension)
				{
					if (!isset($box['DIMENSIONS'][$dimension])) { continue; }

					$dimensionValue = (string)$box['DIMENSIONS'][$dimension]['VALUE'];

					if ($dimensionValue !== '')
					{
						$dimensionValues[] = $dimensionValue;
					}
				}

				$propertyValue = implode('x', $dimensionValues);
			}
			else if (isset($box[$propertyName]))
			{
				$propertyValue = $box[$propertyName];
			}

			$propertyValues[$propertyName] = $propertyValue;
		}

		$box['PROPERTIES'] = $propertyValues;
	}
	unset($box);
}
unset($shipment);