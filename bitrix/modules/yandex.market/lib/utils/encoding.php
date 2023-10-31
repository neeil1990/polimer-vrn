<?php

namespace Yandex\Market\Utils;

use Yandex\Market;
use Bitrix\Main;

class Encoding
{
	public static function getCharset()
	{
		$charset = Main\Config\Configuration::getValue('default_charset');
		$charset = trim($charset);

		if ($charset === '')
		{
			$charset = Main\Application::isUtfMode() ? 'utf-8' : 'windows-1251';
		}

		return $charset;
	}
}