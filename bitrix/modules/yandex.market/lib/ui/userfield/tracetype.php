<?php

namespace Yandex\Market\Ui\UserField;

use Bitrix\Main;
use Yandex\Market;

class TraceType extends StringType
{
	protected static $viewCounter = 0;

	public static function GetAdminListViewHtml($arUserField, $arHtmlControl)
	{
		if ((string)$arHtmlControl['VALUE'] !== '')
		{
			$result = static::formatTracePreview($arHtmlControl['VALUE']);
		}
		else
		{
			$result = '&nbsp;';
		}

		return $result;
	}

	protected static function formatTracePreview($traceString)
	{
		$counter = ++static::$viewCounter;
		$contentsId = 'ymTraceContents' . $counter;

		$result = sprintf('<a href="#" onclick="(new BX.CAdminDialog({ content: BX(\'%s\'), width: 800, height: 700 })).Show(); return false;">', $contentsId);
		$result .= static::getTracePreview($traceString);
		$result .= '</a>';
		$result .= '<div hidden style="display: none;">';
		$result .= sprintf('<pre class="yamarket-code layout--alone" id="%s">', $contentsId);
		$result .= '<pre>' . $traceString . '</pre>';
		$result .= '</div>';

		return $result;
	}

	protected static function getTracePreview($traceString)
	{
		$lineRegexp = sprintf('/\\%1$s([^\\%1$s:]+:\d+)/m', DIRECTORY_SEPARATOR);

		if (preg_match($lineRegexp, $traceString, $matches))
		{
			$result = $matches[1];
		}
		else
		{
			$result = strtok($traceString, "\n");
		}

		return $result;
	}
}