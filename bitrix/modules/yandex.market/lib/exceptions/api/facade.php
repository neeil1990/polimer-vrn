<?php

namespace Yandex\Market\Exceptions\Api;

use Bitrix\Main;
use Yandex\Market;

class Facade
{
	/**
	 * @param Main\Result|Market\Result\Base $result
	 * @param string|null $messageTemplate
	 *
	 * @throws Request
	 */
	public static function handleResult($result, $messageTemplate = null)
	{
		if ($result->isSuccess()) { return; }

		throw static::fromResult($result, $messageTemplate);
	}

	/**
	 * @param Main\Result|Market\Result\Base $result
	 * @param string|null $messageTemplate
	 *
	 * @return Request
	 */
	public static function fromResult($result, $messageTemplate = null)
	{
		$message = static::errorMessage($result, $messageTemplate);
		$code = static::errorCode($result);

		return new Request($message, $code);
	}

	/**
	 * @param Main\Result|Market\Result\Base $result
	 * @param string|null $messageTemplate
	 *
	 * @return string
	 */
	protected static function errorMessage($result, $messageTemplate = null)
	{
		$messages = $result->getErrorMessages();
		$message = implode(PHP_EOL, $messages);

		if ($messageTemplate)
		{
			$message = str_replace('#MESSAGE#', $message, $message);
		}

		return $message;
	}

	/**
	 * @param Main\Result|Market\Result\Base $result
	 *
	 * @return string|null
	 */
	protected static function errorCode($result)
	{
		$code = null;

		foreach ($result->getErrors() as $error)
		{
			$code = $error->getCode();

			if ($code !== null) { break; }
		}

		return $code;
	}
}