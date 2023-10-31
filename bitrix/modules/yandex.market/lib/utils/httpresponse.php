<?php

namespace Yandex\Market\Utils;

use Bitrix\Main;

class HttpResponse
{
	protected static $factory;

	public static function sendJson($data, array $options = [])
	{
		if (class_exists(Main\Engine\Response\Json::class))
		{
			static::sendEngineResponse(new Main\Engine\Response\Json($data), $options);
		}
		else
		{
			$options = array_replace_recursive($options, [
				'headers' => [
					'Content-Type' => 'application/json',
				],
			]);

			static::sendRawResponse(Main\Web\Json::encode($data), $options);
		}
	}

	public static function sendRaw($content, array $options = [])
	{
		$response = Main\Context::getCurrent()->getResponse();

		if (method_exists($response, 'setContent'))
		{
			$response->setContent($content);

			static::sendEngineResponse($response, $options);
		}
		else
		{
			static::sendRawResponse($content, $options);
		}
	}

	protected static function sendEngineResponse(Main\HttpResponse $response, array $options)
	{
		global $APPLICATION;
		$APPLICATION->RestartBuffer();

		/** @var Main\Application $application */
		$application = Main\Application::getInstance();

		if (!empty($options['headers']))
		{
			foreach ($options['headers'] as $name => $value)
			{
				$response->addHeader($name, $value);
			}
		}

		$application->end(0, $response);
	}

	protected static function sendRawResponse($content, array $options)
	{
		global $APPLICATION;
		$APPLICATION->RestartBuffer();

		if (!empty($options['headers']))
		{
			foreach ($options['headers'] as $name => $value)
			{
				header(sprintf('%s: %s', $name, $value));
			}
		}

		echo $content;
		die();
	}
}