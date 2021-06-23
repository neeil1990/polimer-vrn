<?php

namespace Yandex\Market\Utils;

use Bitrix\Main;
use Yandex\Market;

class Agent
{
	public static function parseName($functionCall)
	{
		$result = null;

		if (preg_match('/^(?P<className>.*?)::callAgent\(["\'](?P<method>\w+)["\'](?:,\s*array\s*\((?P<arguments>.*)\)\s*)?\)/si', $functionCall, $matches))
		{
			$result = [
				'class' => $matches['className'],
				'method' => $matches['method'],
				'arguments' => isset($matches['arguments'])
					? static::parseArguments($matches['arguments'])
					: [],
			];
		}

		return $result;
	}

	protected static function parseArguments($argumentsString)
	{
		$result = [];

		foreach (explode(',', $argumentsString) as $argumentSlice)
		{
			$argumentIndex = trim(strtok($argumentSlice, '=>'));
			$argumentValue = trim(strtok('=>'));
			$argumentQuote = Market\Data\TextString::getSubstring($argumentValue, 0, 1);

			if ($argumentQuote === '\'' || $argumentQuote === '"')
			{
				$argumentValue = Market\Data\TextString::getSubstring($argumentValue, 1, -1);
			}

			if ($argumentValue === '') { continue; }

			$result[$argumentIndex] = $argumentValue;
		}

		return $result;
	}
}