<?php

namespace Yandex\Market\Ui\UserField;

use Yandex\Market;
use Bitrix\Main;

class PriceType extends StringType
{
	public static function getAdminListViewHtml($userField, $additionalParameters)
	{
		$value = (float)$additionalParameters['VALUE'];
		$result = '&mdash;';

		if ($value > 0)
		{
			$currencyField = isset($userField['SETTINGS']['CURRENCY_FIELD'])
				? (string)$userField['SETTINGS']['CURRENCY_FIELD']
				: 'CURRENCY';
			$currency = isset($userField['ROW'][$currencyField])
				? (string)$userField['ROW'][$currencyField]
				: '';

			if ($currency !== '')
			{
				$result = Market\Data\Currency::format($value, $currency);
			}
			else
			{
				$result = Market\Data\Price::format($value);
			}
		}

		return $result;
	}
}