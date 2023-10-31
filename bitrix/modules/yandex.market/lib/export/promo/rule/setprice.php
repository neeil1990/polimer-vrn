<?php

namespace Yandex\Market\Export\Promo\Rule;

use Bitrix\Main;
use Yandex\Market;

class SetPrice extends Simple
{
	public static function apply($rule, $price, $currency)
	{
		$currency = (string)$currency;
		$discountCurrency = (string)$rule['DISCOUNT_CURRENCY'];
		$discountPrice = (float)$rule['DISCOUNT_PRICE'];
		$isMatchCurrency = ($currency === '' || $discountCurrency === '' || $currency === $discountCurrency);

		if (!$isMatchCurrency && Main\Loader::includeModule('currency'))
		{
			$precision = static::getPrecision($currency);

			$discountPrice = \CCurrencyRates::ConvertCurrency($discountPrice, $discountCurrency, $currency);
			$discountPrice = roundEx($discountPrice, $precision);
		}

		if ($discountPrice < $price)
		{
			$result = $discountPrice;
		}
		else
		{
			$result = $price;
		}

		return $result;
	}
}