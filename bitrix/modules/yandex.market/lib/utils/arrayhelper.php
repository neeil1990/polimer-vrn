<?php

namespace Yandex\Market\Utils;

class ArrayHelper
{
	public static function column(array $array, $column)
	{
		$result = [];

		foreach ($array as $key => $values)
		{
			if (!isset($values[$column])) { continue; }

			$result[$key] = $values[$column];
		}

		return $result;
	}

	public static function columnToKey(array $array, $column)
	{
		$result = [];

		foreach ($array as $values)
		{
			if (!isset($values[$column])) { continue; }

			$value = $values[$column];

			if (isset($result[$value])) { continue; }

			$result[$value] = $values;
		}

		return $result;
	}

	public static function keysByColumn(array $array, $column)
	{
		$result = [];

		foreach ($array as $key => $values)
		{
			if (!isset($values[$column])) { continue; }

			$value = $values[$column];

			if (isset($result[$value])) { continue; }

			$result[$value] = $key;
		}

		return $result;
	}

	public static function groupBy(array $array, $column, $fallback = null)
	{
		$result = [];

		foreach ($array as $key => $values)
		{
			$value = isset($values[$column]) ? $values[$column] : $fallback;

			if ($value === null) { continue; }

			if (!isset($result[$value])) { $result[$value] = []; }

			$result[$value][$key] = $values;
		}

		return $result;
	}

	public static function flipGroup(array $array)
	{
		$result = [];

		foreach ($array as $key => $value)
		{
			if (!isset($result[$value]))
			{
				$result[$value] = [];
			}

			$result[$value][] = $key;
		}

		return $result;
	}

	public static function firstColumn(array $array, $column)
	{
		$result = null;

		foreach ($array as $values)
		{
			if (!isset($values[$column])) { continue; }

			$result = $values[$column];
			break;
		}

		return $result;
	}

	public static function prefixKeys(array $array, $appendix)
	{
		$result = [];

		foreach ($array as $key => $value)
		{
			$result[$appendix . $key] = $value;
		}

		return $result;
	}
}