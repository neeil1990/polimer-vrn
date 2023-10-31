<?php

namespace Yandex\Market\Export\Entity\Catalog\Price;

use Bitrix\Catalog;
use Bitrix\Main;
use Yandex\Market;

class Event extends Market\Export\Entity\Reference\ElementEvent
{
	protected $currencyMap = [];

	public function onPriceUpdate($iblockId, $offerIblockId, $priceId, $fields)
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
			$productId = static::getPriceProductId($priceId);
		}

		if (
			!static::isElementChangeRegistered($productId, $sourceType, $sourceParams)
			&& static::isTargetElement($iblockId, $offerIblockId, $productId)
		)
		{
			static::registerElementChange($productId, $sourceType, $sourceParams);
		}
	}

	public function onBeforePriceDelete($iblockId, $offerIblockId, $priceId)
	{
		$productId = static::getPriceProductId($priceId);
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

	public function onEntityAfterUpdate($iblockId, $offerIblockId, Main\Event $event)
	{
		$this->onPriceUpdate(
			$iblockId,
			$offerIblockId,
			$event->getParameter('id'),
			$event->getParameter('fields')
		);
	}

	public function onEntityDelete($iblockId, $offerIblockId, Main\Event $event)
	{
		$this->onBeforePriceDelete(
			$iblockId,
			$offerIblockId,
			$event->getParameter('id')
		);
	}

	public function onCurrencyChange($currency, $fields = null)
	{
		$this->registerCurrencyChange($currency);
	}

	public function onCurrencyRateChange($id, $fields)
	{
		if (isset($fields['CURRENCY']))
		{
			$this->registerCurrencyChange($fields['CURRENCY']);
		}
	}

	public function onBeforeCurrencyRateDelete($id)
	{
		$rate = \CCurrencyRates::GetByID($id);

		if ($rate)
		{
			$this->currencyMap[$id] = $rate['CURRENCY'];
		}
	}

	public function onCurrencyRateDelete($id)
	{
		if (isset($this->currencyMap[$id]))
		{
			$currency = $this->currencyMap[$id];

			$this->registerCurrencyChange($currency);
		}
	}

	protected function registerCurrencyChange($currency)
	{
		Market\Watcher\Track\ElementChange::add(Market\Export\Glossary::ENTITY_CURRENCY, $currency);
	}

	protected static function getPriceProductId($priceId)
	{
		$result = null;
		$priceId = (int)$priceId;

		if ($priceId > 0 && Main\Loader::includeModule('catalog'))
		{
			$query = \CPrice::GetList(
				[],
				[ '=ID' => $priceId ],
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
		$result = null;

		// price changes

		if (Main\Loader::includeModule('catalog') && class_exists('Bitrix\Catalog\Model\Price')) // is new version
		{
			$result = [
				[
					'module' => 'catalog',
					'event' => 'Bitrix\Catalog\Model\Price::OnAfterAdd',
					'method' => 'onEntityAfterUpdate',
					'arguments' => [
						$iblockId,
						$offerIblockId
					]
				],
				[
					'module' => 'catalog',
					'event' => 'Bitrix\Catalog\Model\Price::OnAfterUpdate',
					'method' => 'onEntityAfterUpdate',
					'arguments' => [
						$iblockId,
						$offerIblockId
					]
				],
				[
					'module' => 'catalog',
					'event' => 'Bitrix\Catalog\Model\Price::OnDelete',
					'method' => 'onEntityDelete',
					'arguments' => [
						$iblockId,
						$offerIblockId
					]
				]
			];
		}
		else
		{
			$result = [
				[
					'module' => 'catalog',
					'event' => 'OnPriceAdd',
					'method' => 'onPriceUpdate',
					'arguments' => [
						$iblockId,
						$offerIblockId
					]
				],
				[
					'module' => 'catalog',
					'event' => 'OnPriceUpdate',
					'method' => 'onPriceUpdate',
					'arguments' => [
						$iblockId,
						$offerIblockId
					]
				],
				[
					'module' => 'catalog',
					'event' => 'OnBeforePriceDelete',
					'method' => 'onBeforePriceDelete',
					'arguments' => [
						$iblockId,
						$offerIblockId
					]
				]
			];
		}

		// currency

		if (Main\ModuleManager::isModuleInstalled('currency'))
		{
			// -- self currency

			$result[] = [
				'module' => 'currency',
				'event' => 'OnCurrencyAdd',
				'method' => 'onCurrencyChange',
			];

			$result[] = [
				'module' => 'currency',
				'event' => 'OnCurrencyUpdate',
				'method' => 'onCurrencyChange',
			];

			$result[] = [
				'module' => 'currency',
				'event' => 'OnCurrencyDelete',
				'method' => 'onCurrencyChange',
			];

			// -- rates

			$result[] = [
				'module' => 'currency',
				'event' => 'OnCurrencyRateAdd',
				'method' => 'onCurrencyRateChange',
			];

			$result[] = [
				'module' => 'currency',
				'event' => 'OnCurrencyRateUpdate',
				'method' => 'onCurrencyRateChange',
			];

			$result[] = [
				'module' => 'currency',
				'event' => 'OnBeforeCurrencyRateDelete',
				'method' => 'onBeforeCurrencyRateDelete',
			];

			$result[] = [
				'module' => 'currency',
				'event' => 'OnCurrencyRateDelete',
				'method' => 'onCurrencyRateDelete',
			];
		}

		return $result;
	}
}