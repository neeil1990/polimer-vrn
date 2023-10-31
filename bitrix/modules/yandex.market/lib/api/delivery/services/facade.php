<?php

namespace Yandex\Market\Api\Delivery\Services;

use Bitrix\Main;
use Yandex\Market;

class Facade
{
	use Market\Reference\Concerns\HasMessage;

	const CACHE_TTL = 86400;

	/** @return Model\DeliveryServiceCollection */
	public static function load(Market\Api\Reference\HasOauthConfiguration $options, Market\Psr\Log\LoggerInterface $logger = null)
	{
		$cache = Main\Application::getInstance()->getManagedCache();
		$cacheTtl = static::CACHE_TTL;
		$cacheKey = Market\Config::getLangPrefix() . 'DELIVERY_SERVICES';

		if ($cache->read($cacheTtl, $cacheKey))
		{
			$data = $cache->get($cacheKey);

			$result = Model\DeliveryServiceCollection::initialize($data);
		}
		else
		{
			$result = static::fetch($options, $logger);

			$cache->set($cacheKey, $result->toArray());
		}

		return $result;
	}

	protected static function fetch(Market\Api\Reference\HasOauthConfiguration $options, Market\Psr\Log\LoggerInterface $logger = null)
	{
		$request = new Request();

		$request->setLogger($logger);
		$request->setOauthClientId($options->getOauthClientId());
		$request->setOauthToken($options->getOauthToken()->getAccessToken());

		$sendResult = $request->send();

		if (!$sendResult->isSuccess())
		{
			$errorMessage = implode(PHP_EOL, $sendResult->getErrorMessages());
			$exceptionMessage = static::getMessage('FETCH_FAILED', [ '#MESSAGE#' => $errorMessage ], $errorMessage);

			throw new Market\Exceptions\Api\Request($exceptionMessage);
		}

		/** @var $response Response */
		$response = $sendResult->getResponse();

		return $response->getDeliveryServices();
	}
}