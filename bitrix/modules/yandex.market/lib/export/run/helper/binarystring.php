<?php

namespace Yandex\Market\Export\Run\Helper;

class BinaryString 
{
	public static function getLength($str)
	{
		return \function_exists('mb_strlen') ? mb_strlen($str, 'latin1') : strlen($str);
	}

	public static function getSubstring($str, $start, $length = null)
	{
		if (\function_exists('mb_substr'))
		{
			if ($length === null)
			{
				$length = static::getLength($str);
			}

			$result = mb_substr($str, $start, $length, 'latin1');
		}
		else if ($length !== null)
		{
			$result = substr($str, $start, $length);
		}
		else
		{
			$result = substr($str, $start);
		}

		return $result;
	}
	
	public static function getPosition($haystack, $needle, $offset = 0)
	{
		if (\function_exists('mb_orig_strpos'))
		{
			$result = mb_orig_strpos($haystack, $needle, $offset);
		}
		else if (\function_exists('mb_strpos'))
		{
			$result = mb_strpos($haystack, $needle, $offset, 'latin1');
		}
		else
		{
			$result = strpos($haystack, $needle, $offset);
		}

		return $result;
	}
}