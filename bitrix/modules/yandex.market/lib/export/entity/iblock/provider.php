<?php

namespace Yandex\Market\Export\Entity\Iblock;

use Bitrix\Main;
use Bitrix\Iblock;
use Yandex\Market;

class Provider
{
	protected static $contextCache = [];

	public static function getCatalogIblockId($offerIblockId)
	{
		$offerIblockId = (int)$offerIblockId;
		$result = null;

		if ($offerIblockId > 0 && Main\Loader::includeModule('catalog'))
		{
			$catalogData = \CCatalogSku::GetInfoByIBlock($offerIblockId);
			$productIblockId = null;

			if (!empty($catalogData) && $catalogData['CATALOG_TYPE'] === \CCatalogSku::TYPE_OFFERS)
			{
				$productIblockId = (int)$catalogData['PRODUCT_IBLOCK_ID'];
			}

			if ($productIblockId > 0)
			{
				$result = $productIblockId;
			}
		}

		return $result;
	}

	public static function getContext($iblockId)
	{
		$iblockId = (int)$iblockId;

		if (isset(static::$contextCache[$iblockId]))
		{
			$result = static::$contextCache[$iblockId];
		}
		else
		{
			$result = static::loadContext($iblockId);

			static::$contextCache[$iblockId] = $result;
		}

		return $result;
	}

	protected static function loadContext($iblockId)
	{
		$iblockId = (int)$iblockId;
		$result = [
			'IBLOCK_ID' => $iblockId,
			'IBLOCK_NAME' => null,
			'SITE_ID' => null,
			'SITE_LIST' => [],
			'HAS_CATALOG' => false,
			'HAS_OFFER' => false
		];

		// load iblock info

		if ($iblockId > 0 && Main\Loader::includeModule('iblock'))
		{
			// -- base field

			$queryIblockFields = \CIBlock::GetList([], [ 'ID' => $iblockId, 'CHECK_PERMISSIONS' => 'N' ]);

			if ($iblock = $queryIblockFields->Fetch())
			{
				$result['IBLOCK_NAME'] = $iblock['NAME'];
				$result['SITE_ID'] = $iblock['LID'];
			}

			// -- site link

			$queryIblockSite = Iblock\IblockSiteTable::getList([
				'filter' => [ '=IBLOCK_ID' => $iblockId ],
				'select' => [ 'SITE_ID' ]
			]);

			while ($iblockSite = $queryIblockSite->fetch())
			{
				$result['SITE_LIST'][] = $iblockSite['SITE_ID'];
			}
		}

		// load catalog data

		if ($iblockId > 0 && Main\Loader::includeModule('catalog'))
		{
			$catalogData = \CCatalogSku::GetInfoByIBlock($iblockId);

			if (!empty($catalogData))
			{
				$result['HAS_CATALOG'] = true;
				$hasOffers = (
					!empty($catalogData['CATALOG_TYPE'])
					&& (
						$catalogData['CATALOG_TYPE'] === \CCatalogSku::TYPE_PRODUCT
						|| $catalogData['CATALOG_TYPE'] === \CCatalogSku::TYPE_FULL
					)
				);

				if ($hasOffers)
				{
					$result['HAS_OFFER'] = true;
					$result['OFFER_ONLY'] = ($catalogData['CATALOG_TYPE'] === \CCatalogSku::TYPE_PRODUCT);
					$result['OFFER_IBLOCK_ID'] = (int)$catalogData['IBLOCK_ID'];
					$result['OFFER_PROPERTY_ID'] = (int)$catalogData['SKU_PROPERTY_ID'];
				}
			}
		}

		return $result;
	}
}