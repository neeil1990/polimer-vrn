<?php

namespace Yandex\Market\Export\Entity\Currency;

use Bitrix\Main;
use Bitrix\Currency;
use Yandex\Market;

Main\Localization\Loc::loadMessages(__FILE__);

class Source extends Market\Export\Entity\Reference\Source
{
	public function hasCurrencyConversion($fieldName, $settings = null)
	{
		return true;
	}

	public function initializeQueryContext($select, &$queryContext, &$sourceSelect)
	{
		if (!empty($select))
		{
			$convertCurrency = (string)array_shift($select);

			if ($convertCurrency !== '')
			{
				$queryContext['CONVERT_CURRENCY'] = $convertCurrency;
			}
		}
	}

	public function getElementListValues($elementList, $parentList, $selectFields, $queryContext, $sourceValues)
	{
		$result = [];

		foreach ($elementList as $elementId => $element)
		{
			foreach ($selectFields as $field)
			{
				$result[$elementId][$field] = $field;
			}
		}

		return $result;
	}

	public function getFields(array $context = [])
	{
		$result = [];

		if (Main\Loader::includeModule('currency'))
		{
			$currencyList = Currency\CurrencyManager::getCurrencyList();

			foreach ($currencyList as $currency => $title)
			{
				$result[] = [
					'ID' => $currency,
					'VALUE' => $title,
					'TYPE' => Market\Export\Entity\Data::TYPE_CURRENCY_CONVERT,
					'FILTERABLE' => false,
					'SELECTABLE' => true
				];
			}
		}

		return $result;
	}

	protected function getLangPrefix()
	{
		return 'CURRENCY_';
	}
}