<?php

namespace Yandex\Market\Trading\Service\Marketplace\Model;

use Yandex\Market;
use Yandex\Market\Trading\Service as TradingService;

class ShipmentFacade
{
	use Market\Reference\Concerns\HasMessage;

	public static function load(Market\Api\Reference\HasOauthConfiguration $options, $shipmentId, Market\Psr\Log\LoggerInterface $logger = null)
	{
		$request = new TradingService\Marketplace\Api\Shipment\Request();

		$request->setLogger($logger);
		$request->setOauthClientId($options->getOauthClientId());
		$request->setOauthToken($options->getOauthToken()->getAccessToken());
		$request->setCampaignId($options->getCampaignId());
		$request->setShipmentId($shipmentId);

		$sendResult = $request->send();

		if (!$sendResult->isSuccess())
		{
			$errorMessage = implode(PHP_EOL, $sendResult->getErrorMessages());
			$exceptionMessage = self::getMessage('LOAD_FAILED', [ '#MESSAGE#' => $errorMessage ], $errorMessage);

			throw new Market\Exceptions\Api\Request($exceptionMessage);
		}

		/** @var TradingService\Marketplace\Api\Shipment\Response $response */
		$response = $sendResult->getResponse();

		return $response->getShipment();
	}

	public static function loadOrdersInfo(Market\Api\Reference\HasOauthConfiguration $options, $shipmentId, Market\Psr\Log\LoggerInterface $logger = null)
	{
		$request = new TradingService\Marketplace\Api\ShipmentOrdersInfo\Request();

		$request->setLogger($logger);
		$request->setOauthClientId($options->getOauthClientId());
		$request->setOauthToken($options->getOauthToken()->getAccessToken());
		$request->setCampaignId($options->getCampaignId());
		$request->setShipmentId($shipmentId);

		$sendResult = $request->send();

		if (!$sendResult->isSuccess())
		{
			$errorMessage = implode(PHP_EOL, $sendResult->getErrorMessages());
			$exceptionMessage = self::getMessage('LOAD_ORDERS_INFO_FAILED', [ '#MESSAGE#' => $errorMessage ], $errorMessage);

			throw new Market\Exceptions\Api\Request($exceptionMessage);
		}

		/** @var TradingService\Marketplace\Api\ShipmentOrdersInfo\Response $response */
		$response = $sendResult->getResponse();

		return $response->getOrdersInfo();
	}
}