<?php

namespace Yandex\Market\Utils\UserField;

use Bitrix\Main;
use Yandex\Market;

class DependField
{
	const RULE_ANY = 'ANY';
	const RULE_EXCLUDE = 'EXCLUDE';
	const RULE_EMPTY = 'EMPTY';

	public static function test($rules, $values)
	{
		$logicMatchAny = (isset($rules['LOGIC']) && $rules['LOGIC'] === 'OR');
		$result = !$logicMatchAny;

		foreach ($rules as $fieldName => $rule)
		{
			if ($fieldName === 'LOGIC') { continue; }

			$value = Market\Utils\Field::getChainValue($values, $fieldName, Market\Utils\Field::GLUE_BRACKET);

			switch ($rule['RULE'])
			{
				case static::RULE_EMPTY:
					$isDependValueEmpty = static::testIsEmpty($value);
					$isMatch = ($isDependValueEmpty === $rule['VALUE']);
				break;

				case static::RULE_ANY:
					$isMatch = static::applyRuleAny($rule['VALUE'], $value);
				break;

				case static::RULE_EXCLUDE:
					$isMatch = !static::applyRuleAny($rule['VALUE'], $value);
				break;

				default:
					$isMatch = true;
				break;
			}

			if ($logicMatchAny === $isMatch)
			{
				$result = $isMatch;
				break;
			}
		}

		return $result;
	}

	protected static function testIsEmpty($value)
	{
		$result = true;

		if (is_array($value))
		{
			foreach ($value as $one)
			{
				if (!static::testIsEmpty($one))
				{
					$result = false;
					break;
				}
			}
		}
		else
		{
			$result = Market\Utils\Value::isEmpty($value) || (is_scalar($value) && (string)$value === '0');
		}

		return $result;
	}

	protected static function applyRuleAny($ruleValue, $formValue)
	{
		$isRuleMultiple = is_array($ruleValue);
		$isFormMultiple = is_array($formValue);

		if ($isFormMultiple && $isRuleMultiple)
		{
			$intersect = array_intersect($ruleValue, $formValue);
			$result = !empty($intersect);
		}
		else if ($isFormMultiple)
		{
			/** @noinspection TypeUnsafeArraySearchInspection */
			$result = in_array($ruleValue, $formValue);
		}
		else if ($isRuleMultiple)
		{
			/** @noinspection TypeUnsafeArraySearchInspection */
			$result = in_array($formValue, $ruleValue);
		}
		else
		{
			/** @noinspection TypeUnsafeComparisonInspection */
			$result = ($formValue == $ruleValue);
		}

		return $result;
	}
}