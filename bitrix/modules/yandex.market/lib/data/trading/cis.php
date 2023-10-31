<?php

namespace Yandex\Market\Data\Trading;

use Yandex\Market\Data\TextString;

class Cis
{
	const GROUP_SPLITTER = "\u{001d}";

	/**
	 * @deprecated
	 * @noinspection PhpUnused
	 */
	public static function fromMarkingCode($markingCode)
	{
		if (preg_match('/^(01\d{14}21[A-Za-z0-9!"%&\'*+.\/_,:;=<>?\\\-]{13,27}?)91/', $markingCode, $matches))
		{
			$result = $matches[1];
		}
		else
		{
			$result = TextString::getSubstring($markingCode, 0, 31);
		}

		return $result;
	}

	public static function diff(array $first, array $second)
	{
		$firstCompare = array_map(static function($code) { return static::compareCodeBase($code); }, $first);
		$secondCompare = array_map(static function($code) { return static::compareCodeBase($code); }, $second);
		$diff = array_diff($firstCompare, $secondCompare);

		return array_intersect_key($first, $diff);
	}

	protected static function compareCodeBase($markingCode)
	{
		$symbolsCode = static::cutSplitter($markingCode);
		$partials = static::splitPartials($symbolsCode);

		return $partials !== null ? $partials[0] : static::cutSplitter($markingCode);
	}

	public static function formatMarkingCode($markingCode)
	{
		if (TextString::getPosition($markingCode, static::GROUP_SPLITTER) !== false) // already formatted
		{
			return $markingCode;
		}

		$formattedCode = static::unifySplitter($markingCode);

		if ($formattedCode !== $markingCode) // already contains group separator
		{
			$formattedCode = str_replace(' ', '', $formattedCode);

			if (TextString::getPosition($formattedCode, static::GROUP_SPLITTER) === 0)
			{
				$formattedCode = TextString::getSubstring($formattedCode, 1);
			}

			return $formattedCode;
		}

		$partials = static::splitPartials($markingCode);

		if ($partials !== null)
		{
			return implode(static::GROUP_SPLITTER, $partials);
		}

		return $markingCode;
	}

	protected static function unifySplitter($markingCode)
	{
		return str_replace(['<FNC1>', '<GS>', '\u001d'], static::GROUP_SPLITTER, $markingCode);
	}

	protected static function cutSplitter($markingCode)
	{
		return str_replace(['<FNC1>', '<GS>', '\u001d', static::GROUP_SPLITTER], '', $markingCode);
	}

	protected static function splitPartials($markingCode)
	{
		$result = null;
		$symbols = '[A-Za-z0-9!"%&\'*+.\/_,:;=<>?()\\\-]';
		$testCode = str_replace(' ', '', $markingCode);
		$regexps = [
			sprintf('/^(01\d{14}21%1$s{13,27}?)(91%1$s{4})(92.+)$/', $symbols),
			sprintf('/^(01\d{14}21%1$s{6}?)(91%1$s{4})(92.+)$/', $symbols),
			sprintf('/^(01\d{14}21%1$s{6}?)(93%1$s{4})$/', $symbols),
			sprintf('/^(01\d{14}21%1$s{6}?)(93%1$s{4})(3103\d{6})$/', $symbols),
			sprintf('/^(01\d{14}21%1$s{13}?)(93%1$s{4})$/', $symbols),
		];

		foreach ($regexps as $regexp)
		{
			if (!preg_match($regexp, $testCode, $matches)) { continue; }

			array_shift($matches);

			$result = $matches;
		}

		return $result;
	}
}