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
			'host' => static::httpHost($request),
		];
		$hostWithProtocol = static::compileTemplate('#protocol#://#host#', $variables);

		return $hostWithProtocol . $path;
	}

	public static function httpHost(Main\HttpRequest $request = null)
	{
		if ($request === null)
		{
			$globalRequest = Main\Context::getCurrent()->getRequest();

			if (!($globalRequest instanceof Main\HttpRequest)) { return null; }

			$request = $globalRequest;
		}

		$host = $request->getHttpHost();
		$converter = \CBXPunycode::GetConverter();

		if ($converter->IsEncoded($host))
		{
			$host = $converter->Decode($host);
		}

		return $host;
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