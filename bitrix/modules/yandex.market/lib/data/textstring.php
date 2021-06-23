<?php

namespace Yandex\Market\Data;

use Bitrix\Main;
use Yandex\Market;

class TextString
{
	public static function getLength($string)
	{
		if (\function_exists('mb_strlen'))
		{
			$result = mb_strlen($string, LANG_CHARSET);
		}
		else
		{
			$result = strlen($string);
		}

		return $result;
	}

	public static function getPosition($haystack, $needle, $offset = 0)
	{
		if (\function_exists('mb_strpos'))
		{
			$result = mb_strpos($haystack, $needle, $offset, LANG_CHARSET);
		}
		else
		{
			$result = strpos($haystack, $needle, $offset);
		}

		return $result;
	}

	public static function getLastPosition($haystack, $needle, $offset = 0)
	{
		if (\function_exists('mb_strrpos'))
		{
			$result = mb_strrpos($haystack, $needle, $offset, LANG_CHARSET);
		}
		else
		{
			$result = strrpos($haystack, $needle, $offset);
		}

		return $result;
	}

	public static function getPositionCaseInsensitive($haystack, $needle, $offset = 0)
	{
		if (\function_exists('mb_stripos'))
		{
			$result = mb_stripos($haystack, $needle, $offset, LANG_CHARSET);
		}
		else
		{
			$result = stripos($haystack, $needle, $offset);
		}

		return $result;
	}

	public static function getSubstring($string, $from, $length = null)
	{
		if (\function_exists('mb_substr'))
		{
			$result = mb_substr($string, $from, $length, LANG_CHARSET);
		}
		else
		{
			$result = substr($string, $from, $length);
		}

		return $result;
	}

	public static function toUpper($string)
	{
		if (\function_exists('mb_strtoupper'))
		{
			$result = mb_strtoupper($string, LANG_CHARSET);
		}
		else
		{
			$result = strtoupper($string);
		}

		return $result;
	}

	public static function toLower($string)
	{
		if (\function_exists('mb_strtolower'))
		{
			$result = mb_strtolower($string, LANG_CHARSET);
		}
		else
		{
			$result = strtolower($string);
		}

		return $result;
	}

	public static function ucfirst($string)
	{
		return
			static::toUpper(static::getSubstring($string, 0, 1))
			. static::getSubstring($string, 1);
	}

	public static function padLeft($string, $length, $pad = ' ')
	{
		$stringLength = static::getLength($string);
		$lengthDiff = $length - $stringLength;

		if ($lengthDiff > 0)
		{
			$string = str_repeat($pad, $lengthDiff) . $string;
		}

		return $string;
	}

	public static function match($pattern, $subject, &$matches = null, $flags = 0, $offset = 0)
	{
		$needConvert = !Main\Application::isUtfMode() && static::hasPatternUnicode($pattern);

		if ($needConvert)
		{
			$subject = Main\Text\Encoding::convertEncoding($subject, LANG_CHARSET, 'UTF-8');
		}

		$result = preg_match($pattern, $subject, $matches, $flags, $offset);

		if ($result && $needConvert)
		{
			$matches = Main\Text\Encoding::convertEncoding($matches, 'UTF-8', LANG_CHARSET);
		}

		return $result;
	}

	protected static function hasPatternUnicode($pattern)
	{
		$wrapSymbol = static::getSubstring($pattern, 0, 1);
		$wrapClosePosition = static::getLastPosition($pattern, $wrapSymbol, 1);
		$result = false;

		if ($wrapClosePosition !== false)
		{
			$modifiers = static::getSubstring($pattern, $wrapClosePosition);
			$result = (static::getPosition($modifiers, 'u') !== false);
		}

		return $result;
	}

	public static function lcfirst($string)
	{
		return
			static::toLower(static::getSubstring($string, 0, 1))
			. static::getSubstring($string, 1);
	}
}