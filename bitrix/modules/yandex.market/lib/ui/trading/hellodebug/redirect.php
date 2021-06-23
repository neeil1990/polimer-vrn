<?php

namespace Yandex\Market\Ui\Trading\HelloDebug;

use Bitrix\Main;
use Yandex\Market;

class Redirect extends Market\Reference\Event\Base
{
	public static function getHandlers()
	{
		return [
			[
				'module' => 'main',
				'event' => 'onBeforeLocalRedirect',
			],
		];
	}

	public static function onBeforeLocalRedirect(&$url, $skip_security_check, $bExternal)
	{
		$request = Main\Context::getCurrent()->getRequest();
		$requestUri = $request->getRequestUri();
		$basePath = static::getBasePath();

		if (Market\Data\TextString::getPosition($requestUri, $basePath) === 0)
		{
			$trace = Market\Utils\Trace::getTraceUntil('LocalRedirect');
			$firstLevel = reset($trace);
			$reason = 'LOCAL_REDIRECT';
			$data = [
				'url' => $url,
			];

			if ($firstLevel !== false)
			{
				$data += Market\Utils\Trace::getLevelData($firstLevel);

				if (isset($data['module']))
				{
					$reason = 'MODULE_REDIRECT';
				}
			}

			Response::send($reason, $data, $trace);
		}
	}

	protected static function getBasePath()
	{
		return BX_ROOT . '/services/' . Market\Config::getModuleName() . '/trading/';
	}

	public static function install()
	{
		foreach (static::getHandlers() as $handler)
		{
			static::register($handler);
		}
	}

	public static function uninstall()
	{
		foreach (static::getHandlers() as $handler)
		{
			static::unregister($handler);
		}
	}
}