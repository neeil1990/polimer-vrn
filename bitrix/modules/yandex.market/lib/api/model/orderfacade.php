<?php

namespace Yandex\Market\Api\Model;

use Yandex\Market;
use Bitrix\Main;

class OrderFacade
{
	use Market\Reference\Concerns\HasLang;

	protected static function includeMessages()
	{
		Main\Localization\Loc::loadMessages(__FILE__);
	}

	public static function loadList(Market\Api\Reference\HasOauthConfiguration $options, array $parameters = null, Market\Psr\Log\LoggerInterface $logger = null)
	{
		$request = static::createLoadListRequest();

		$request->setLogger($logger);
		$request->setOauthClientId($options->getOauthClientId());
		$request->setOauthToken($options->getOauthToken()->getAccessToken());
		$request->setCampaignId($options->getCampaignId());

		if ($parameters !== null)
		{
			$request->processParameters($parameters);
		}

		$sendResult = $request->send();

		if (!$sendResult->isSuccess())
		{
			$errorMessage = implode(PHP_EOL, $sendResult->getErrorMessages());
			$exceptionMessage = static::getLang('API_ORDERS_FETCH_FAILED', [ '#MESSAGE#' => $errorMessage ]);

			throw new Main\SystemException($exceptionMessage);
		}

		/** @var $response Market\Api\Partner\Orders\Response */
		$response = $sendResult->getResponse();

		return $response->getOrderCollection();
	}

	protected static function createLoadListRequest()
	{
		return new Market\Api\Partner\Orders\Request();
	}

	public static function load(Market\Api\Reference\HasOauthConfiguration $options, $orderId, Market\Psr\Log\LoggerInterface $logger = null)
	{
		$request = static::createLoadRequest();

		$request->setLogger($logger);
		$request->setOauthClientId($options->getOauthClientId());
		$request->setOauthToken($options->getOauthToken()->getAccessToken());
		$request->setCampaignId($options->getCampaignId());
		$request->setOrderId($orderId);

		$sendResult = $request->send();

		if (!$sendResult->isSuccess())
		{
			$errorMessage = implode(PHP_EOL, $sendResult->getErrorMessages());
			$exceptionMessage = static::getLang('API_ORDER_FETCH_FAILED', [ '#MESSAGE#' => $errorMessage ], $errorMessage);

			throw new Market\Exceptions\Api\Request($exceptionMessage);
		}

		/** @var $response Market\Api\Partner\Order\Response */
		$response = $sendResult->getResponse();

		return $response->getOrder();
	}

	protected static function createLoadRequest()
	{
		return new Market\Api\Partner\Order\Request();
	}

	public static function submitStatus(Market\Api\Reference\HasOauthConfiguration $options, $orderId, $status, $subStatus = null, Market\Psr\Log\LoggerInterface $logger = null, array $payload = [])
	{
		$request = static::createSubmitStatusRequest();

		$request->setLogger($logger);
		$request->setCampaignId($options->getCampaignId());
		$request->setOauthClientId($options->getOauthClientId());
		$request->setOauthToken($options->getOauthToken()->getAccessToken());
		$request->setOrderId($orderId);
		$request->setStatus($status);
		$request->setSubStatus($subStatus);
		$request->setPayload($payload);

		$sendResult = $request->send();

		if (!$sendResult->isSuccess())
		{
			$errorMessage = implode(PHP_EOL, $sendResult->getErrorMessages());
			$exceptionMessage = static::getLang('API_ORDER_SUBMIT_STATUS_FAILED', [ '#MESSAGE#' => $errorMessage ], $errorMessage);

			throw new Market\Exceptions\Api\Request($exceptionMessage);
		}

		/** @var Market\Api\Partner\SendStatus\Response $response */
		$response = $sendResult->getResponse();

		return $response->getOrder();
	}

	protected static function createSubmitStatusRequest()
	{
		return new Market\Api\Partner\SendStatus\Request();
	}
}