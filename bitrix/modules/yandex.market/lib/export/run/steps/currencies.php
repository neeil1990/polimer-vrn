<?php

namespace Yandex\Market\Export\Run\Steps;

use Bitrix\Main;
use Bitrix\Currency;
use Yandex\Market;

class Currencies extends Base
{
	public function getName()
	{
		return 'currency';
	}

	public function run($action, $offset = null)
	{
		$result = new Market\Result\Step();

		$context = $this->getContext();
		$currencyList = $this->getCurrencyList($context);

		$tagValuesList = $this->buildTagValuesList([], $currencyList, $context);

		$this->setRunAction($action);
		$this->extendData($tagValuesList, $currencyList, $context);
		$this->writeData($tagValuesList, $currencyList, $context);

		return $result;
	}

	public function getFormatTag(Market\Export\Xml\Format\Reference\Base $format, $type = null)
	{
		return $format->getCurrency();
	}

	public function getFormatTagParentName(Market\Export\Xml\Format\Reference\Base $format)
	{
		return $format->getCurrencyParentName();
	}

	protected function getStorageDataClass()
	{
		return Market\Export\Run\Storage\CurrencyTable::getClassName();
	}

	protected function getDataLogEntityType()
	{
		return Market\Logger\Table::ENTITY_TYPE_EXPORT_RUN_CURRENCY;
	}

	protected function buildTagValues($elementId, $tagDescription, $currencyData, $context, Market\Export\Xml\Tag\Base $root = null)
	{
		$result = new Market\Result\XmlValue();

		$result->addTag('currency', '', [
			'id' => $currencyData['CURRENCY'],
			'rate' => $currencyData['RATE']
		]);

		return $result;
	}

	protected function getIgnoredTypeChanges()
	{
		return [
			Market\Export\Run\Manager::ENTITY_TYPE_PROMO => true,
			Market\Export\Run\Manager::ENTITY_TYPE_COLLECTION => true,
			Market\Export\Run\Manager::ENTITY_TYPE_GIFT => true,
		];
	}

	protected function getCurrencyList($context)
	{
		$currencyIds = $this->getUsedCurrencyIds($context);
		$result = [];

		if (empty($currencyIds) && !empty($context['CONVERT_CURRENCY']))
		{
			$currencyIds[] = $context['CONVERT_CURRENCY'];
		}

		if (!empty($currencyIds))
		{
			$configuredBase = $this->getConfiguredBaseCurrency();
			$rates = $this->getRates($currencyIds, $configuredBase);
			$type = $this->getType();

			// format used values

			foreach ($currencyIds as $currencyId)
			{
				$currencyFormatted = $type->format($currencyId);

				$result[$currencyFormatted] = [
					'CURRENCY' => $currencyFormatted,
					'RATE' => isset($rates['RATES'][$currencyId]) ? $rates['RATES'][$currencyId] : $this->getAutomaticRate($currencyFormatted)
				];
			}

			// push base currency, if not set

			$baseCurrency = ($rates['BASE'] !== null ? $rates['BASE'] : $type->getDefaultBase());
			$baseCurrencyFormatted = $type->format($baseCurrency);

			if (!in_array($baseCurrency, $currencyIds, true))
			{
				$result[$baseCurrencyFormatted] = [
					'CURRENCY' => $baseCurrencyFormatted,
					'RATE' => 1
				];
			}

			// exclude rates

			if (Market\Config::getOption('export_currency_rate', 'N') !== 'Y')
			{
				$result = array_intersect_key($result, [
					$baseCurrencyFormatted => true,
				]);
			}
		}

		return $result;
	}

	protected function getUsedCurrencyIds($context)
	{
		$result = [];

		$query = Market\Export\Run\Storage\OfferTable::getList([
			'group' => [ 'CURRENCY_ID' ],
			'select' => [ 'CURRENCY_ID' ],
			'filter' => [
				'=SETUP_ID' => $context['SETUP_ID'],
				'=STATUS' => static::STORAGE_STATUS_SUCCESS
			]
		]);

		while ($row = $query->fetch())
		{
			$currencyId = trim($row['CURRENCY_ID']);

			if ($currencyId !== '')
			{
				$result[] = $currencyId;
			}
		}

		return $result;
	}

	protected function getConfiguredBaseCurrency()
	{
		$result = null;

		/** @var Market\Export\IblockLink\Model $iblockLink */
		foreach ($this->getSetup()->getIblockLinkCollection() as $iblockLink)
		{
			$currencyDescription = $iblockLink->getTagDescription('currencyId');

			if (!isset($currencyDescription['SETTINGS']['BASE_CURRENCY'])) { continue; }

			$settingValue = (string)$currencyDescription['SETTINGS']['BASE_CURRENCY'];

			if (
				$settingValue !== ''
				&& $settingValue !== Market\Export\Xml\Tag\CurrencyId::BASE_CURRENCY_DEFAULT
				&& $this->getType()->isBase($settingValue)
			)
			{
				$result = $settingValue;
				break;
			}
		}

		return $result;
	}

	protected function getRates($currencyIds, $base = null)
	{
		$result = [
			'BASE' => $base,
			'RATES' => []
		];
		$leftRatesCount = count($currencyIds);
		$methods = [
			'getCurrencyModuleRates',
			'getAutomaticRates'
		];

		foreach ($methods as $method)
		{
			$rateResult = $this->{$method}($currencyIds, $result['BASE']);

			if ($result['BASE'] === null && isset($rateResult['BASE']))
			{
				$result['BASE'] = $rateResult['BASE'];
			}

			foreach ($rateResult['RATES'] as $currency => $rate)
			{
				if (!isset($result['RATES'][$currency]))
				{
					$result['RATES'][$currency] = $rate;
					$leftRatesCount--;
				}
			}

			if ($leftRatesCount <= 0)
			{
				break;
			}
		}

		return $result;
	}

	protected function getCurrencyModuleRates($usedCurrencies, $baseCurrency = null)
	{
		$result = [
			'BASE' => null,
			'RATES' => []
		];

		list($moduleCurrencies, $moduleBaseCurrency) = $this->queryCurrencyModuleItems();
		$matchedCurrencies = array_intersect($usedCurrencies, $moduleCurrencies);

		if ($baseCurrency === null)
		{
			$baseCurrency = $moduleBaseCurrency;
		}

		$baseCurrency = $this->normalizeBaseCurrency($baseCurrency, $matchedCurrencies);
		$convertCurrency = $this->sanitizeConvertCurrency($baseCurrency, $moduleCurrencies);

		if ($convertCurrency !== null)
		{
			$result['BASE'] = $baseCurrency;
			$result['RATES'] = $this->fetchCurrencyModuleRates($matchedCurrencies, $convertCurrency);
		}

		return $result;
	}

	protected function queryCurrencyModuleItems()
	{
		$currencies = [];
		$base = null;

		if (Main\Loader::includeModule('currency'))
		{
			$query = Currency\CurrencyTable::getList([
				'select' => [
					'CURRENCY',
					'BASE'
				],
				'order' => [
					'SORT' => 'asc'
				]
			]);

			while ($row = $query->fetch())
			{
				if ($row['BASE'] === 'Y')
				{
					$base = $row['CURRENCY'];
				}

				$currencies[] = $row['CURRENCY'];
			}
		}

		return [$currencies, $base];
	}

	protected function fetchCurrencyModuleRates($currencies, $baseCurrency)
	{
		$result = [];

		if (Main\Loader::includeModule('currency'))
		{
			foreach ($currencies as $currency)
			{
				if ($currency === $baseCurrency)
				{
					$result[$currency] = 1;
				}
				else
				{
					$currencyRate = \CCurrencyRates::GetConvertFactor($currency, $baseCurrency);

					if ($currencyRate > 0) // invalid result
					{
						$result[$currency] = $currencyRate;
					}
				}
			}
		}

		return $result;
	}

	protected function getAutomaticRates($usedCurrencyList, $baseCurrency = null)
	{
		$baseCurrency = $this->normalizeBaseCurrency($baseCurrency, $usedCurrencyList);
		$result = [
			'BASE' => $baseCurrency,
			'RATES' => []
		];

		foreach ($usedCurrencyList as $currency)
		{
			$currencyRate = null;

			if ($currency === $baseCurrency)
			{
				$currencyRate = 1;
			}
			else
			{
				$currencyRate = $this->getAutomaticRate($currency);
			}

			$result['RATES'][$currency] = $currencyRate;
		}

		return $result;
	}

	protected function getAutomaticRate($currency)
	{
		return 'CB';
	}

	protected function normalizeBaseCurrency($baseCurrency, $currencyList)
	{
		$type = $this->getType();
		$result = null;

		if ($type->isBase($baseCurrency))
		{
			$result = $baseCurrency;
		}
		else
		{
			foreach ($currencyList as $currency)
			{
				if ($type->isBase($currency))
				{
					$result = $currency;
					break;
				}
			}
		}

		return $result;
	}

	protected function sanitizeConvertCurrency($currency, $existsCurrencies)
	{
		$result = null;

		if ($currency === null)
		{
			// nothing
		}
		else if (in_array($currency, $existsCurrencies, true))
		{
			$result = $currency;
		}
		else
		{
			$revertCurrency = $this->getType()->revert($currency);

			if (in_array($revertCurrency, $existsCurrencies, true))
			{
				$result = $revertCurrency;
			}
		}

		return $result;
	}

	/**
	 * @return Market\Type\CurrencyType
	 */
	protected function getType()
	{
		return Market\Type\Manager::getType(
			Market\Type\Manager::TYPE_CURRENCY
		);
	}
}