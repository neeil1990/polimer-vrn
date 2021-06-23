<?php

namespace Yandex\Market\Utils;

use Bitrix\Main;
use Yandex\Market;

class Field
{
	const GLUE_DOT = 'dot';
	const GLUE_BRACKET = 'bracket';

	public static function getChainValue($values, $key, $glue = Field::GLUE_DOT)
	{
		$keyParts = static::splitKey($key, $glue);
		$lastLevel = $values;

		foreach ($keyParts as $keyPart)
		{
			if (isset($lastLevel[$keyPart]))
			{
				$lastLevel = $lastLevel[$keyPart];
			}
			else
			{
				$lastLevel = null;
				break;
			}
		}

		return $lastLevel;
	}

	public static function setChainValue(&$values, $key, $value, $glue = Field::GLUE_DOT)
	{
		$keyParts = static::splitKey($key, $glue);
		$keyPartIndex = 0;
		$keyPartCount = count($keyParts);
		$lastLevel = &$values;

		foreach ($keyParts as $keyPart)
		{
			if ($keyPartCount === $keyPartIndex + 1)
			{
				$lastLevel[$keyPart] = $value;
			}
			else
			{
				if (!isset($lastLevel[$keyPart]) || !is_array($lastLevel[$keyPart]))
				{
					$lastLevel[$keyPart] = [];
				}

				$lastLevel = &$lastLevel[$keyPart];
			}

			$keyPartIndex++;
		}
	}

	public static function pushChainValue(&$values, $key, $value, $glue = Field::GLUE_DOT)
	{
		$keyParts = static::splitKey($key, $glue);
		$keyPartIndex = 0;
		$keyPartCount = count($keyParts);
		$lastLevel = &$values;

		foreach ($keyParts as $keyPart)
		{
			if ($keyPartCount === $keyPartIndex + 1)
			{
				if (!isset($lastLevel[$keyPart]))
				{
					$lastLevel[$keyPart] = [];
				}

				$lastLevel[$keyPart][] = $value;
			}
			else
			{
				if (!isset($lastLevel[$keyPart]) || !is_array($lastLevel[$keyPart]))
				{
					$lastLevel[$keyPart] = [];
				}

				$lastLevel = &$lastLevel[$keyPart];
			}

			$keyPartIndex++;
		}
	}

	public static function unsetChainValue(&$values, $key, $glue = Field::GLUE_DOT)
	{
		$keyParts = static::splitKey($key, $glue);
		$keyPartIndex = 0;
		$keyPartCount = count($keyParts);
		$lastLevel = &$values;

		foreach ($keyParts as $keyPart)
		{
			if (!isset($lastLevel[$keyPart]))
			{
				break;
			}
			else if ($keyPartCount === $keyPartIndex + 1)
			{
				unset($lastLevel[$keyPart]);
			}
			else
			{
				$lastLevel = &$lastLevel[$keyPart];
			}

			$keyPartIndex++;
		}
	}

	public static function splitKey($key, $glue = Field::GLUE_DOT)
	{
		if (is_array($key))
		{
			$result = $key;
		}
		else if ($glue === static::GLUE_DOT)
		{
			$result = explode('.', $key);
		}
		else if ($glue === static::GLUE_BRACKET)
		{
			$result = static::splitKeyByBrackets($key);
		}
		else
		{
			throw new Main\ArgumentException(sprintf('unknown glue %s', $glue));
		}

		return $result;
	}

	protected static function splitKeyByBrackets($key)
	{
		$keyOffset = 0;
		$keyLength = Market\Data\TextString::getLength($key);
		$keyChain = [];

		do
		{
			$keyPart = null;

			if ($keyOffset === 0)
			{
				$arrayEnd = Market\Data\TextString::getPosition($key, '[');

				if ($arrayEnd === false)
				{
					$keyPart = $key;
					$keyOffset = $keyLength;
				}
				else
				{
					$keyPart = Market\Data\TextString::getSubstring($key, $keyOffset, $arrayEnd - $keyOffset);
					$keyOffset = $arrayEnd + 1;
				}
			}
			else
			{
				$arrayEnd = Market\Data\TextString::getPosition($key, ']', $keyOffset);

				if ($arrayEnd === false)
				{
					$keyPart = Market\Data\TextString::getSubstring($key, $keyOffset);
					$keyOffset = $keyLength;
				}
				else
				{
					$keyPart = Market\Data\TextString::getSubstring($key, $keyOffset, $arrayEnd - $keyOffset);
					$keyOffset = $arrayEnd + 2;
				}
			}

			if ((string)$keyPart !== '')
			{
				$keyChain[] = $keyPart;
			}
			else
			{
				break;
			}
		}
		while ($keyOffset < $keyLength);

		return $keyChain;
	}
}