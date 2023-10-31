<?php

namespace Yandex\Market\Export\Entity\Catalog\Product;

use Bitrix\Iblock;
use Bitrix\Main;
use Yandex\Market;

class Event extends Market\Export\Entity\Reference\ElementEvent
{
	public function onProductUpdate($iblockId, $offerIblockId, $elementId, $fields)
	{
        $sourceType = $this->getType();
        $sourceParams = [
            'IBLOCK_ID' => $iblockId,
            'OFFER_IBLOCK_ID' => $offerIblockId
        ];

		if (
			!static::isElementChangeRegistered($elementId, $sourceType, $sourceParams)
			&& static::isTargetElement($iblockId, $offerIblockId, $elementId)
		)
		{
			static::registerElementChange($elementId, $sourceType, $sourceParams);
		}
	}

	public function onEntityAfterUpdate($iblockId, $offerIblockId, Main\Event $event)
	{
		$elementId = $event->getParameter('id');
		$elementIblockId = null;
		$externalData = $event->getParameter('external_fields');
        $sourceType = $this->getType();
        $sourceParams = [
            'IBLOCK_ID' => $iblockId,
            'OFFER_IBLOCK_ID' => $offerIblockId
        ];

		if (isset($externalData['IBLOCK_ID']))
		{
			$elementIblockId = $externalData['IBLOCK_ID'];
		}

		if (
			!static::isElementChangeRegistered($elementId, $sourceType, $sourceParams)
			&& static::isTargetElement($iblockId, $offerIblockId, $elementId, $elementIblockId)
		)
		{
			static::registerElementChange($elementId, $sourceType, $sourceParams);
		}
	}

	protected function getEventsForIblock($iblockId, $offerIblockId = null)
	{
		$result = null;

		if (Main\Loader::includeModule('catalog') && class_exists('Bitrix\Catalog\Model\Product')) // is new version
		{
			$result = [
				[
					'module' => 'catalog',
					'event' => 'Bitrix\Catalog\Model\Product::OnAfterAdd',
					'method' => 'onEntityAfterUpdate',
					'arguments' => [
						$iblockId,
						$offerIblockId
					]
				],
				[
					'module' => 'catalog',
					'event' => 'Bitrix\Catalog\Model\Product::OnAfterUpdate',
					'method' => 'onEntityAfterUpdate',
					'arguments' => [
						$iblockId,
						$offerIblockId
					]
				]
				// no delete event
			];
		}
		else
		{
			$result = [
				[
					'module' => 'catalog',
					'event' => 'OnProductAdd',
					'method' => 'onProductUpdate',
					'arguments' => [
						$iblockId,
						$offerIblockId
					]
				],
				[
					'module' => 'catalog',
					'event' => 'OnProductUpdate',
					'method' => 'onProductUpdate',
					'arguments' => [
						$iblockId,
						$offerIblockId
					]
				]
				// no delete event
			];
		}

		return $result;
	}
}