<?php

namespace Yandex\Market\Export\Promo\Rule;

use Bitrix\Main;
use Yandex\Market;

class Simple
{
	public static function apply($rule, $price, $currency)
	{
		$result = $price;
		$currency = (string)$currency;
		$discountValue = $rule['DISCOUNT_VALUE'];
		$discountLimit = isset($rule['DISCOUNT_LIMIT']) ? $rule['DISCOUNT_LIMIT'] : 0;
		$discountCurrency = (string)$rule['DISCOUNT_CURRENCY'];
		$isMatchCurrency = ($currency === '' || $discountCurrency === '' || $currency === $discountCurrency);
		$discountSum = 0;

		switch ($rule['DISCOUNT_UNIT'])
		{
			case Market\Export\Promo\Table::DISCOUNT_UNIT_CURRENCY:
				if ($isMatchCurrency)
				{
					$discountSum = $discountValue;
				}
				else if (Main\Loader::includeModule('currency'))
				{
					$discountSum = \CCurrencyRates::ConvertCurrency($discountValue, $discountCurrency, $currency);
				}
			break;

			case Market\Export\Promo\Table::DISCOUNT_UNIT_PERCENT:
				$discountSum = $result * ($discountValue / 100);
			break;
		}

		if ($discountLimit > 0 && !$isMatchCurrency && Main\Loader::includeModule('currency'))
		{
			$discountLimit = \CCurrencyRates::ConvertCurrency($discountLimit, $discountCurrency, $currency);
		}

		if ($discountLimit > 0 && $discountSum > $discountLimit)
		{
			$discountSum = $discountLimit;
		}

		if ($discountSum > 0)
		{
			$precision = static::getPrecision($currency);

			$result -= $discountSum;
			$result = roundEx($result, $precision);
		}

		return $result;
	}

	protected static function getPrecision($currency)
	{
		$result = static::getSalePrecision();
		$currencyPrecision = Market\Data\Currency::getPrecision($currency);

		if ($currencyPrecision !== false && $currencyPrecision < $result)
		{
			$result = $currencyPrecision;
		}

		return $result;
	}

	protected static function getSalePrecision()
	{
		return (int)Main\Config\Option::get('sale', 'value_precision', 2, '');
	}
}