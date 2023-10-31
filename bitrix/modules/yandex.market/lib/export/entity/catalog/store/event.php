<?php

namespace Yandex\Market\Export\Entity\Catalog\Store;

use Bitrix\Iblock;
use Bitrix\Main;
use Yandex\Market;

class Event extends Market\Export\Entity\Reference\ElementEvent
{
	public function onStoreProductUpdate($iblockId, $offerIblockId, $amountId, $fields)
	{
		$productId = null;
        $sourceType = $this->getType();
        $sourceParams = [
            'IBLOCK_ID' => $iblockId,
            'OFFER_IBLOCK_ID' => $offerIblockId
        ];

		if (isset($fields['PRODUCT_ID']))
		{
			$productId = $fields['PRODUCT_ID'];
		}
		else
		{
			$productId = static::getCatalogStoreProductId($amountId);
		}

		if (
			!static::isElementChangeRegistered($productId, $sourceType, $sourceParams)
			&& static::isTargetElement($iblockId, $offerIblockId, $productId)
		)
		{
			static::registerElementChange($productId, $sourceType, $sourceParams);
		}
	}

	public function onBeforeStoreProductDelete($iblockId, $offerIblockId, $amountId)
	{
		$productId = static::getCatalogStoreProductId($amountId);
        $sourceType = $this->getType();
        $sourceParams = [
            'IBLOCK_ID' => $iblockId,
            'OFFER_IBLOCK_ID' => $offerIblockId
        ];

		if (
			!static::isElementChangeRegistered($productId, $sourceType, $sourceParams)
			&& static::isTargetElement($iblockId, $offerIblockId, $productId)
		)
		{
			static::registerElementChange($productId, $sourceType, $sourceParams);
		}
	}

	protected static function getCatalogStoreProductId($amountId)
	{
		$result = null;
		$amountId = (int)$amountId;

		if ($amountId > 0 && Main\Loader::includeModule('catalog'))
		{
			$query = \CCatalogStoreProduct::GetList(
				[],
				[ '=ID' => $amountId ],
				false,
				false,
				[ 'PRODUCT_ID' ]
			);

			if ($row = $query->Fetch())
			{
				$result = (int)$row['PRODUCT_ID'];
			}
		}

		return $result;
	}

	protected function getEventsForIblock($iblockId, $offerIblockId = null)
	{
		return [
			[
				'module' => 'catalog',
				'event' => 'OnStoreProductAdd',
				'method' => 'onStoreProductUpdate',
				'arguments' => [
					$iblockId,
					$offerIblockId
				]
			],
			[
				'module' => 'catalog',
				'event' => 'OnStoreProductUpdate',
				'method' => 'onStoreProductUpdate',
				'arguments' => [
					$iblockId,
					$offerIblockId
				]
			],
			[
				'module' => 'catalog',
				'event' => 'OnBeforeStoreProductDelete',
				'method' => 'onBeforeStoreProductDelete',
				'arguments' => [
					$iblockId,
					$offerIblockId
				]
			]
		];
	}
}