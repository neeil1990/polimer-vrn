<?php

namespace Yandex\Market\Ui\UserField\Helper;

class Value
{
	public static function asSingle($userField, $htmlControl)
	{
		$value = static::extractFromField($userField, $htmlControl);

		return static::isSingle($value) ? $value : null;
	}

	public static function asMultiple($userField, $htmlControl)
	{
		$value = static::extractFromField($userField, $htmlControl);

		if (static::isMultiple($value))
		{
			$result = $value;
		}
		else if (static::isSingle($value) && !static::isEmpty($value))
		{
			$result = [ $value ];
		}
		else
		{
			$result = [];
		}

		return $result;
	}

	protected static function isSingle($value)
	{
		return !is_array($value);
	}

	protected static function isMultiple($value)
	{
		return is_array($value);
	}

	protected static function isEmpty($value)
	{
		return !is_scalar($value) || (string)$value === '';
	}

	protected static function extractFromField($userField, $htmlControl)
	{
		if (isset($userField['VALUE']))
		{
			$result = $userField['VALUE'];
		}
		else if (isset($htmlControl['VALUE']) && ($htmlControl['VALUE'] !== '' || !array_key_exists('VALUE', $userField)))
		{
			$result = $htmlControl['VALUE'];
		}
		else if ($userField['ENTITY_VALUE_ID'] < 1 && !empty($userField['SETTINGS']['DEFAULT_VALUE']))
		{
			$result = $userField['SETTINGS']['DEFAULT_VALUE'];
		}
		else
		{
			$result = null;
		}

		return $result;
	}
}