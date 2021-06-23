<?php

namespace Yandex\Market\Ui\UserField;

use Yandex\Market;

class LogType extends EnumerationType
{
	protected static $optionCache = null;

	public static function GetAdminListViewHTML($arUserField, $arHtmlControl)
	{
		$result = '';
		$option = static::getOption($arUserField, $arHtmlControl['VALUE']);

		if ($option)
		{
			$imgType = 'green';

			if (isset($option['LOG_LEVEL']))
			{
				switch ($option['LOG_LEVEL'])
				{
					case Market\Logger\Level::EMERGENCY:
					case Market\Logger\Level::ALERT:
					case Market\Logger\Level::CRITICAL:
					case Market\Logger\Level::ERROR:
						$imgType = 'red';
					break;

					case Market\Logger\Level::WARNING:
					case Market\Logger\Level::NOTICE:
						$imgType = 'yellow';
					break;

					case Market\Logger\Level::INFO:
						$imgType = 'green';
					break;

					default:
						$imgType = 'grey';
					break;
				}
			}

			$result .= '<nobr>';
			$result .= '<img class="b-log-icon" src="/bitrix/images/yandex.market/' .  $imgType . '.gif" width="14" height="14" alt="" />';
			$result .= $option['VALUE'];
			$result .= '</nobr>';
		}

		return $result;
	}

	protected static function getOption($arUserField, $id)
	{
		$result = false;

		if (static::$optionCache === null)
		{
			static::$optionCache = [];

			$query = call_user_func([ $arUserField['USER_TYPE']['CLASS_NAME'], 'GetList' ], $arUserField);

			while ($option = $query->fetch())
			{
				static::$optionCache[$option['ID']] = $option;

				if ($option['ID'] == $id)
				{
					$result = $option;
				}
			}
		}
		else if (isset(static::$optionCache[$id]))
		{
			$result = static::$optionCache[$id];
		}

		return $result;
	}
}