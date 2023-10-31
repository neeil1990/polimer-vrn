<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) { die(); }

use Bitrix\Main\Localization\Loc;
use Yandex\Market;

if (empty($arResult['BOX_DIMENSIONS'])) { return; }

foreach ($arResult['BOX_DIMENSIONS'] as $name => &$description)
{
	if (isset($description['UNIT']))
	{
		if ($name === 'WEIGHT')
		{
			$unitFormatted = Market\Data\Weight::getUnitTitle($description['UNIT'], 'short');
		}
		else
		{
			$unitFormatted = Market\Data\Size::getUnitTitle($description['UNIT'], 'short');
		}

		$description['UNIT_FORMATTED'] = $unitFormatted;
	}
}
unset($description);