<?php

namespace Yandex\Market\Ui\UserField;

use Yandex\Market;
use Bitrix\Main;

class TradingOrderItemType extends StringType
{
	public static function getAdminListViewHtml($userField, $additionalParameters)
	{
		$result = '';

		if (static::isValidItem($userField['VALUE']))
		{
			$result = static::formatItemValue($userField['VALUE']);
		}

		return $result;
	}

	public static function getAdminListViewHtmlMulty($userField, $additionalParameters)
	{
		$items = [];

		if (is_array($userField['VALUE']))
		{
			foreach ($userField['VALUE'] as $item)
			{
				if (static::isValidItem($item))
				{
					$items[] = static::formatItemValue($item);
				}
			}
		}

		return implode(PHP_EOL, $items);
	}

	protected static function isValidItem($item)
	{
		return is_array($item) && !empty($item['OFFER_ID']);
	}

	protected static function formatItemValue($item)
	{
		$result = '<div>';
		$result .= $item['QUANTITY'] . '&nbsp;x&nbsp;';

		if (isset($item['URL']))
		{
			$result .= '<a href="' . htmlspecialcharsbx($item['URL']) . '">' . htmlspecialcharsbx($item['OFFER_NAME']) . '</a>';
		}
		else
		{
			$result .= htmlspecialcharsbx($item['OFFER_NAME']);
		}

		$result .= '&nbsp;[' . $item['OFFER_ID'] . ']';
		$result .= '</div>';

		return $result;
	}
}