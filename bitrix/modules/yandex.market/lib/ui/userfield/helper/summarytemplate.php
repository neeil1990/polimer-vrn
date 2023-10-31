<?php

namespace Yandex\Market\Ui\UserField\Helper;

use Bitrix\Main;
use Yandex\Market;

class SummaryTemplate
{
	public static function render($template, $vars)
	{
		$usedKeys = static::getUsedKeys($template);
		list($replaces, $removes) = static::splitValidVariables($vars, $usedKeys);

		$result = static::applyRemoveVariables($template, $removes);
		$result = static::applyReplaceVariables($result, $replaces);

		return $result;
	}

	protected static function applyRemoveVariables($template, $keys)
	{
		$result = $template;

		foreach ($keys as $key)
		{
			$result = static::removeVariable($result, $key);
		}

		return $result;
	}

	protected static function applyReplaceVariables($template, $replaces)
	{
		$result = $template;

		foreach ($replaces as $key => $value)
		{
			$result = static::replaceVariable($result, $key, $value);
		}

		return $result;
	}

	public static function getUsedKeys($template)
	{
		if (preg_match_all('/#([A-Z0-9_.]+?)#/', $template, $matches))
		{
			$result = $matches[1];
		}
		else
		{
			$result = [];
		}

		return $result;
	}

	public static function normalizeNames($fields)
	{
		$result = [];

		foreach ($fields as $name => $field)
		{
			$normalized = static::normalizeFieldName($name);

			$result[$normalized] = $field;
		}

		return $result;
	}

	protected static function normalizeFieldName($name)
	{
		if (Market\Data\TextString::getPosition($name, '[') === false) { return $name; }

		$parts = [];

		foreach (explode('[', $name) as $part)
		{
			$part = rtrim($part, ']');

			if ($part === '') { continue; }

			$parts[] = $part;
		}

		return implode('.', $parts);
	}

	protected static function splitValidVariables($vars, $keys)
	{
		$replaces = [];
		$invalid = [];

		foreach ($keys as $key)
		{
			if (isset($vars[$key]) && static::isValidVariable($vars[$key]))
			{
				$replaces[$key] = $vars[$key];
			}
			else
			{
				$invalid[] = $key;
			}
		}

		return [ $replaces, $invalid ];
	}

	protected static function isValidVariable($value)
	{
		return (isset($value) && is_scalar($value) && (string)$value !== '');
	}

	protected static function removeVariable($template, $key)
	{
		$search = '#' . $key . '#';
		$result = $template;

		while (($searchPosition = Market\Data\TextString::getPosition($result, $search)) !== false)
		{
			$before = Market\Data\TextString::getSubstring($result, 0, $searchPosition);
			$after = Market\Data\TextString::getSubstring($result, $searchPosition + strlen($search));
			$beforeParentheses = Market\Data\TextString::getPosition($before, '(');
			$afterParentheses = Market\Data\TextString::getPosition($after, ')');

			if ($beforeParentheses !== false && $afterParentheses !== false) // inside parentheses
			{
				$beforeOuter = Market\Data\TextString::getSubstring($before, 0, $beforeParentheses + 1);
				$beforeInner = Market\Data\TextString::getSubstring($before, $beforeParentheses + 1);
				$afterOuter = Market\Data\TextString::getSubstring($after, $afterParentheses);
				$afterInner = Market\Data\TextString::getSubstring($after, 0, $afterParentheses);
				$beforeInner = static::trimRightPart($beforeInner);
				$afterInner = static::trimLeftPart($afterInner, $beforeInner);

				if ($beforeInner === '' && $afterInner === '') // then remove parentheses
				{
					$before = Market\Data\TextString::getSubstring($beforeOuter, 0, $beforeParentheses);
					$after = Market\Data\TextString::getSubstring($afterOuter, 1);
				}
				else
				{
					$before = $beforeOuter . $beforeInner;
					$after = $afterInner . $afterOuter;
				}
			}
			else
			{
				$before = static::trimRightPart($before);
				$after = static::trimLeftPart($after, $before);
			}

			if (isset($after[0]) && $after[0] === '(')
			{
				$after = ' ' . $after;
			}

			$result = $before . $after;
		}

		return $result;
	}

	protected static function trimRightPart($part)
	{
		return preg_replace('/,?[^#.,()]*$/', '', $part);
	}

	protected static function trimLeftPart($part, $before = '')
	{
		$part = preg_replace('/^[^#.,()]+/', '', $part);

		if (isset($part[0]) && $part[0] === ',' && ($before === '' || Market\Data\TextString::getSubstring($before, -1) === '.'))
		{
			$part = Market\Data\TextString::getSubstring($part, 1);

			if ($before === '')
			{
				$part = ltrim($part);
			}
		}

		return $part;
	}

	protected static function replaceVariable($template, $key, $value)
	{
		return str_replace('#' . $key . '#' , $value, $template);
	}
}