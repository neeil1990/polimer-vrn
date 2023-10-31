<?php

namespace Yandex\Market\Utils;

use Yandex\Market;

class ActionFilter
{
	public static function isMatch($filter, $item)
	{
		if (!is_array($filter)) { return true; }

		$result = true;

		foreach ($filter as $field => $condition)
		{
			if (Market\Data\TextString::getPosition($field, '!') === 0)
			{
				$field = Market\Data\TextString::getSubstring($field, 1);
				$inverse = true;
			}
			else
			{
				$inverse = false;
			}

			$value = isset($item[$field]) ? $item[$field] : null;

			$isConditionIterable = is_array($condition);
			$isValueIterable = is_array($value);

			if ($isConditionIterable || $isValueIterable)
			{
				$conditionIterable = $isConditionIterable ? $condition : [ $condition ];
				$valueIterable = $isValueIterable ? $value : [ $value ];

				$match = (count(array_intersect($conditionIterable, $valueIterable)) > 0);
			}
			else
			{
				$match = (string)$condition === (string)$value;
			}

			if ($inverse) { $match = !$match; }

			if (!$match)
			{
				$result = false;
				break;
			}
		}

		return $result;
	}
}