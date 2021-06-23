<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) { die(); }

use Bitrix\Main;
use Bitrix\Iblock;

$arResult['IBLOCK_DATA'] = [];

if (!empty($arResult['ITEM']['IBLOCK_LINK']))
{
	$iblockIds = [];

	foreach ($arResult['ITEM']['IBLOCK_LINK'] as $iblockLink)
	{
		if (!empty($iblockLink['IBLOCK_ID']))
		{
			$iblockId = (int)$iblockLink['IBLOCK_ID'];

			if ($iblockId > 0)
			{
				$iblockIds[] = $iblockId;
			}
		}
	}

	if (!empty($iblockIds) && Main\Loader::includeModule('iblock'))
	{
		$query = Iblock\IblockTable::getList([
			'filter' => [
				'=ID' => $iblockIds
			],
			'select' => [
				'ID',
				'NAME'
			]
		]);

		while ($iblock = $query->fetch())
		{
			$arResult['IBLOCK_DATA'][$iblock['ID']] = $iblock;
		}
	}
}