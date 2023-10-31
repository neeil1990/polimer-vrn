<?php

namespace Yandex\Market\Utils;

class XmlValue
{
	protected static $allowedEntities = [
		'&amp;' => true,
		'&nbsp;' => true,
		'&lt;' => true,
		'&gt;' => true,
		'&quot;' => true,
		'&apos;' => true,
	];

	protected static $replaceEntities = [
		'&nbsp;' => ' ',
		'&ensp;' => ' ',
		'&emsp;' => ' ',
		'&ndash;' => '-',
		'&mdash;' => '-',
	];

	public static function escape($value)
	{
		static $encoding = null;

		if ($encoding === null)
		{
			$encoding = defined('BX_UTF') ? 'UTF-8' : 'ISO-8859-1';
		}

		$value = preg_replace_callback("/&[a-z]{4,5};/", [static::class, 'applyReplace'], $value); // restore valid chars
		$value = htmlspecialchars($value, ENT_NOQUOTES|ENT_XML1, $encoding, false); // apply special chars (no need quotes, simplexml restores original quotes)
		$value = preg_replace_callback("/&([^;]*;)/", [static::class, 'applyEscape'], $value); // escape not valid entities
		$value = preg_replace("/[\x1-\x8\xB-\xD\xE-\x1F]/", '', $value); // remove special chars

		return $value;
	}

	protected static function applyReplace(array $matches)
	{
		return isset(static::$replaceEntities[$matches[0]])
			? static::$replaceEntities[$matches[0]]
			: $matches[0];
	}

	protected static function applyEscape(array $matches)
	{
		return isset(static::$allowedEntities[$matches[0]])
			? $matches[0]
			: ('&amp;' . $matches[1]);
	}
}