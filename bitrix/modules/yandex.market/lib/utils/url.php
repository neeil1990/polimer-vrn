<?php

namespace Yandex\Market\Utils;

use Yandex\Market;
use Bitrix\Main;

class Url
{
	public static function absolutizePath($path, $variables = [])
	{
		$request = Main\Context::getCurrent()->getRequest();
		$variables += [
			'protocol' => $request->isHttps() ? 'https' : 'http',
			'host' => $request->getHttpHost(),
		];
		$hostWithProtocol = static::compileTemplate('#protocol#://#host#', $variables);

		return $hostWithProtocol . $path;
	}

	protected static function compileTemplate($template, $variables)
	{
		$result = $template;

		foreach ($variables as $key => $value)
		{
			$result = str_replace('#' . $key . '#', $value, $result);
		}

		return $result;
	}
}