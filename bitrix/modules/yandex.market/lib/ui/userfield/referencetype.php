<?php
namespace Yandex\Market\Ui\UserField;

use Bitrix\Main;

class ReferenceType extends EnumerationType
{
	public static function GetList($arUserField)
	{
		static $cache = [];

		$dataClass = static::getDataClass($arUserField);

		if ($dataClass === null)
		{
			$values = [];
		}
		else if (isset($cache[$dataClass]))
		{
			$values = $cache[$dataClass];
		}
		else
		{
			$values = static::fetchList($dataClass);
			$cache[$dataClass] = $values;
		}

		if (isset($arUserField['SETTINGS']['INCLUDE_VALUES']))
		{
			$values = static::applyValuesIncludeFilter(
				$values,
				$arUserField['SETTINGS']['INCLUDE_VALUES'],
				!empty($arUserField['SETTINGS']['INCLUDE_INVERSE'])
			);
		}

		$result = new \CDBResult();
		$result->InitFromArray($values);

		return $result;
	}

	protected static function fetchList($dataClass)
	{
		$values = [];

		/** @var Main\Entity\DataManager $dataClass */
		$query = $dataClass::getList([
			'filter' => static::fetchFilter(),
			'select' => [ 'ID', 'NAME' ],
		]);

		while ($row = $query->fetch())
		{
			$values[] = [
				'ID' => $row['ID'],
				'VALUE' => sprintf('[%s] %s', $row['ID'], $row['NAME']),
			];
		}

		return $values;
	}

	protected static function fetchFilter()
	{
		return [];
	}

	protected static function getDataClass($userField)
	{
		$result = null;

		if (isset($userField['SETTINGS']['DATA_CLASS']))
		{
			$result = Main\Entity\Base::normalizeEntityClass($userField['SETTINGS']['DATA_CLASS']);
		}

		return $result;
	}

	protected static function applyValuesIncludeFilter($values, $include, $inverse = false)
	{
		$includeMap = array_flip($include);

		foreach ($values as $valueKey => $value)
		{
			$isIncluded = isset($includeMap[$value['ID']]);

			if ($isIncluded === $inverse)
			{
				unset($values[$valueKey]);
			}
		}

		return $values;
	}
}