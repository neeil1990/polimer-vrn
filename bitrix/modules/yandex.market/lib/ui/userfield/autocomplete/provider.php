<?php

namespace Yandex\Market\Ui\UserField\Autocomplete;

use Bitrix\Main;

abstract class Provider
{
	public static function searchByName($searchQuery)
	{
		throw new Main\NotImplementedException();
	}

	public static function getList()
	{
		throw new Main\NotImplementedException();
	}

	public static function getPropertyValue($property, $value)
	{
		throw new Main\NotImplementedException();
	}

	public static function getClassName()
	{
		return '\\' . static::class;
	}

	public static function getValueForAutoComplete($property, $value)
	{
		$result = '';
		$propertyValue = static::getPropertyValue($property, $value);

		if ($propertyValue !== null)
		{
			$result = $propertyValue['NAME'] . ' [' . $propertyValue['ID'] . ']';
		}

		return $result;
	}

	public static function getValueForAutoCompleteMulti($property, $valueList)
	{
		$result = [];
		$valueList = (array)$valueList;

		foreach ($valueList as $value)
		{
			$valueText = static::getValueForAutoComplete($property, $value);

			if ($valueText !== '')
			{
				$result[] = $valueText;
			}
		}

		return $result;
	}
}