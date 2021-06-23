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
		if (preg_match_all('/#([A-Z0-9_]+?)#/', $template, $matches))
		{
			$result = $matches[1];
		}
		else
		{
			$result = [];
		}

		return $result;
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
			$before = static::trimRightPart($before);
			$after = Market\Data\TextString::getSubstring($result, $searchPosition + strlen($search));
			$after = static::trimLeftPart($after);

			if (isset($after[0]) && $after[0] === ',' && Market\Data\TextString::getSubstring($before, -1) === '.')
			{
				$after = Market\Data\TextString::getSubstring($after, 1);
			}

			$result = $before . $after;
		}

		return $result;
	}

	protected static function trimRightPart($part)
	{
		return preg_replace('/,?[^#.,]+$/', '', $part);
	}

	protected static function trimLeftPart($part)
	{
		return preg_replace('/^[^#.,]+/', '', $part);
	}

	protected static function replaceVariable($template, $key, $value)
	{
		return str_replace('#' . $key . '#' , $value, $template);
	}
}