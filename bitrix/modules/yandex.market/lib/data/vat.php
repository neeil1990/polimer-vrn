<?php

namespace Yandex\Market\Data;

use Bitrix\Main;
use Yandex\Market;

class Vat
{
	use Market\Reference\Concerns\HasLang;

	protected static $type;

	protected static function includeMessages()
	{
		Main\Localization\Loc::loadMessages(__FILE__);
	}

	public static function getTitle($vat)
	{
		if (preg_match('/^VAT_(\d+)(?:_(\d+))?$/', $vat, $matches))
		{
			$firstPercent = (int)$matches[1];
			$secondPercent = (string)$matches[2];

			if ($secondPercent !== '')
			{
				$format = $firstPercent . '/' . $secondPercent;
			}
			else
			{
				$format = $firstPercent . '%';
			}

			$result = static::getLang('DATA_VAT_FORMAT', [ '#FORMAT#' => $format ], $format);
		}
		else
		{
			$result = static::getLang('DATA_VAT_' . $vat, null, $vat);
		}

		return $result;
	}

	public static function convertForService($rate)
	{
		$type = static::getType();

		return $type->format($rate);
	}

	/**
	 * @return Market\Type\VatType
	 */
	protected static function getType()
	{
		if (static::$type === null)
		{
			static::$type = new Market\Type\VatType();
		}

		return static::$type;
	}
}