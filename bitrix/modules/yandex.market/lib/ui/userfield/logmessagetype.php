<?php

namespace Yandex\Market\Ui\UserField;

use Bitrix\Main;
use Yandex\Market;

class LogMessageType extends StringType
{
	const FORMAT_DEBUG = 'debug';
	const FORMAT_TEXT = 'text';

	protected static $debugCounter = 0;
	protected static $debugBase;

	public static function GetAdminListViewHtml($userField, $htmlControl)
	{
		$value = Helper\MixedValue::asSingle($userField, $htmlControl);

		if ($value !== null && !is_scalar($value)) { $value = print_r($value, true); }

		$value = (string)$value;

		return $value !== '' ? static::renderMessage($value) : '&nbsp;';
	}

	protected static function renderMessage($message)
	{
		$type = static::getMessageType($message);

		switch ($type)
		{
			case static::FORMAT_DEBUG:
				$result = static::renderDebugMessage($message);
			break;

			default:
				$result = nl2br($message);
			break;
		}

		return $result;
	}

	protected static function getMessageType($message)
	{
		if (Market\Data\TextString::getPosition($message, 'Array') === 0)
		{
			$result = static::FORMAT_DEBUG;
		}
		else
		{
			$result = static::FORMAT_TEXT;
		}

		return $result;
	}

	protected static function renderDebugMessage($message)
	{
		$counter = ++static::$debugCounter;
		$contentsId = 'ymLogMessageDebugContents' . static::getDebugBase() . $counter;

		$result = sprintf('<a href="#" onclick="(new BX.CAdminDialog({ content: BX(\'%s\'), width: 800, height: 700 })).Show(); return false;">', $contentsId);
		$result .= static::getDebugPreview($message);
		$result .= '</a>';
		$result .= '<div hidden style="display: none;">';
		$result .= sprintf('<pre class="yamarket-code layout--alone" id="%s">', $contentsId);
		$result .= '<pre>' . $message . '</pre>';
		$result .= '</div>';

		return $result;
	}

	protected static function getDebugBase()
	{
		if (static::$debugBase === null)
		{
			static::$debugBase = randString(5);
		}

		return static::$debugBase;
	}

	protected static function getDebugPreview($message)
	{
		$result = null;
		$offset = 0;

		while (preg_match('/\[([a-z]+)\]\s*=(?:&gt;|>)\s*Array/i', $message, $matches, PREG_OFFSET_CAPTURE, $offset))
		{
			if ($matches[1][0] !== 'pager')
			{
				$result = $matches[1][0];
				break;
			}
			else
			{
				$offset = $matches[1][1];
			}
		}

		if ($result === null)
		{
			$result = lcfirst(strtok($message, "\n"));
		}

		return $result;
	}
}