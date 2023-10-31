<?php

namespace Yandex\Market\Api\Model;

use Yandex\Market;
use Bitrix\Main;

class OutletFacade
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
			$exceptionMessage = static::getLang('API_OUTLETS_FETCH_FAILED', [ '#MESSAGE#' => $errorMessage ]);

			throw new Main\SystemException($exceptionMessage);
		}

		/** @var $response Market\Api\Partner\Outlets\Response */
		$response = $sendResult->getResponse();

		return $response->getOutletCollection();
	}

	protected static function createLoadListRequest()
	{
		return new Market\Api\Partner\Outlets\Request();
	}

	public static function load(Market\Api\Reference\HasOauthConfiguration $options, $outletId, Market\Psr\Log\LoggerInterface $logger = null)
	{
		$request = static::createLoadRequest();

		$request->setLogger($logger);
		$request->setOauthClientId($options->getOauthClientId());
		$request->setOauthToken($options->getOauthToken()->getAccessToken());
		$request->setCampaignId($options->getCampaignId());
		$request->setOutletId($outletId);

		$sendResult = $request->send();

		if (!$sendResult->isSuccess())
		{
			$errorMessage = implode(PHP_EOL, $sendResult->getErrorMessages());
			$exceptionMessage = static::getLang('API_OUTLET_FETCH_FAILED', [ '#MESSAGE#' => $errorMessage ], $errorMessage);

			throw new Market\Exceptions\Api\Request($exceptionMessage);
		}

		/** @var $response Market\Api\Partner\Outlet\Response */
		$response = $sendResult->getResponse();

		return $response->getOutlet();
	}

	protected static function createLoadRequest()
	{
		return new Market\Api\Partner\Outlet\Request();
	}
}