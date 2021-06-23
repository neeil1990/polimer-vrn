<?php

namespace Yandex\Market\Utils\UserField;

use Bitrix\Main;
use Yandex\Market;

class DependField
{
	const RULE_ANY = 'ANY';
	const RULE_EMPTY = 'EMPTY';

	public static function test($rules, $values)
	{
		$result = true;

		foreach ($rules as $fieldName => $rule)
		{
			$value = isset($values[$fieldName]) ? $values[$fieldName] : null;

			switch ($rule['RULE'])
			{
				case static::RULE_EMPTY:
					$isDependValueEmpty = Market\Utils\Value::isEmpty($value) || (is_scalar($value) && (string)$value === '0');
					$isMatch = ($isDependValueEmpty === $rule['VALUE']);
				break;

				case static::RULE_ANY:
					$isMatch = static::applyRuleAny($rule['VALUE'], $value);
				break;

				default:
					$isMatch = true;
				break;
			}

			if (!$isMatch)
			{
				$result = false;
				break;
			}
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