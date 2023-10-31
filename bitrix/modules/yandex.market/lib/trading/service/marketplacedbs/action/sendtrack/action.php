<?php

namespace Yandex\Market\Trading\Service\MarketplaceDbs\Action\SendTrack;

use Yandex\Market;
use Bitrix\Main;
use Yandex\Market\Trading\Entity as TradingEntity;
use Yandex\Market\Trading\Service as TradingService;

/** @property TradingService\MarketplaceDbs\Provider $provider */
/** @property Request $request */
class Action extends TradingService\Reference\Action\DataAction
{
	use Market\Reference\Concerns\HasMessage;
	use TradingService\Common\Concerns\Action\HasOrder;
	use TradingService\Common\Concerns\Action\HasOrderMarker;

	public function __construct(TradingService\MarketplaceDbs\Provider $provider, TradingEntity\Reference\Environment $environment, array $data)
	{
		parent::__construct($provider, $environment, $data);
	}

	public function getAudit()
	{
		return Market\Logger\Trading\Audit::SEND_TRACK;
	}

	protected function createRequest(array $data)
	{
		return new Request($data);
	}

	public function process()
	{
		try
		{
			if (!$this->isMatchDeliveryId()) { return; }

			$orderId = $this->request->getOrderId();
			$trackCode = $this->request->getTrackCode();
			$deliveryServiceId = $this->getDeliveryServiceId($orderId);

			if (!$this->isTrackCodeChanged($trackCode)) { return; }

			$this->sendTrackCode($orderId, $trackCode, $deliveryServiceId);
			$this->logTrackCode($orderId, $trackCode, $deliveryServiceId);
			$this->saveData($orderId, $trackCode, $deliveryServiceId);

			$this->resolveOrderMarker(true);
		}
		catch (Exceptions\OrderWithoutShipment $exception)
		{
			$this->logOrderWithoutShipment($exception->getMessage());
		}
		catch (Market\Exceptions\Api\Request $exception)
		{
			$sendResult = new Main\Result();
			$sendResult->addError(new Main\Error(
				$exception->getMessage(),
				$exception->getCode()
			));

			$this->resolveOrderMarker(false, $sendResult);
			throw $exception;
		}
	}

	protected function isMatchDeliveryId()
	{
		$serviceKey = $this->provider->getUniqueKey();
		$orderId = $this->request->getOrderId();
		$deliveryId = Market\Trading\State\OrderData::getValue($serviceKey, $orderId, 'DELIVERY_ID');

		return ($deliveryId === null || (string)$deliveryId === $this->request->getDeliveryId());
	}

	protected function isTrackCodeChanged($trackCode)
	{
		$serviceKey = $this->provider->getUniqueKey();
		$orderId = $this->request->getOrderId();
		$stored = Market\Trading\State\OrderData::getValue($serviceKey, $orderId, 'TRACK_CODE');

		return (string)$trackCode !== (string)$stored;
	}

	protected function getDeliveryServiceId($orderId)
	{
		return
			$this->getDeliveryServiceIdFromStoredData($orderId)
			?: $this->getDeliveryServiceIdFromApi($orderId);
	}

	protected function getDeliveryServiceIdFromStoredData($orderId)
	{
		$serviceKey = $this->provider->getUniqueKey();

		return Market\Trading\State\OrderData::getValue($serviceKey, $orderId, 'DELIVERY_SERVICE_ID');
	}

	protected function getDeliveryServiceIdFromApi($orderId)
	{
		$options = $this->provider->getOptions();
		$order = Market\Api\Model\OrderFacade::load($options, $orderId);

		return $order->getDelivery()->getServiceId();
	}

	protected function saveData($orderId, $trackCode, $deliveryServiceId)
	{
		$serviceKey = $this->provider->getUniqueKey();

		Market\Trading\State\OrderData::setValues($serviceKey, $orderId, [
			'DELIVERY_SERVICE_ID' => $deliveryServiceId,
			'TRACK_CODE' => $trackCode,
		]);
	}

	protected function sendTrackCode($orderId, $trackCode, $deliveryServiceId)
	{
		$request = $this->createSendTrackCodeRequest($orderId, $trackCode, $deliveryServiceId);
		$sendResult = $request->send();

		if (!$sendResult->isSuccess())
		{
			$exception = $this->makeTrackCodeException($sendResult);

			throw $exception;
		}
	}

	protected function makeTrackCodeException(Market\Result\Base $sendResult)
	{
		$errors = $sendResult->getErrors();
		$error = reset($errors);

		if ($error !== false && $error->getCode() === 'NOT_FOUND')
		{
			return new Exceptions\OrderWithoutShipment($error->getMessage());
		}

		$errorMessage = implode(PHP_EOL, $sendResult->getErrorMessages());
		$exceptionMessage = static::getMessage('SEND_ERROR', [ '#MESSAGE#' => $errorMessage ], $errorMessage);

		return new Market\Exceptions\Api\Request($exceptionMessage);
 	}

	protected function createSendTrackCodeRequest($orderId, $trackCode, $deliveryServiceId)
	{
		$options = $this->provider->getOptions();
		$logger = $this->provider->getLogger();
		$result = new TradingService\MarketplaceDbs\Api\SendTrack\Request();

		$result->setLogger($logger);
		$result->setCampaignId($options->getCampaignId());
		$result->setOauthClientId($options->getOauthClientId());
		$result->setOauthToken($options->getOauthToken()->getAccessToken());
		$result->setOrderId($orderId);
		$result->setTrackCode($trackCode);
		$result->setDeliveryServiceId($deliveryServiceId);

		return $result;
	}

	protected function logTrackCode($orderId, $trackCode, $deliveryServiceId)
	{
		$logger = $this->provider->getLogger();
		$message = static::getMessage('SEND_LOG', [
			'#EXTERNAL_ID#' => $orderId,
			'#TRACK_CODE#' => $trackCode,
			'#DELIVERY_SERVICE_ID#' => $deliveryServiceId,
		]);

		$logger->info($message, [
			'AUDIT' => Market\Logger\Trading\Audit::SEND_TRACK,
			'ENTITY_TYPE' => TradingEntity\Registry::ENTITY_TYPE_ORDER,
			'ENTITY_ID' => $this->request->getOrderNumber(),
		]);
	}

	protected function logOrderWithoutShipment($errorMessage)
	{
		$logger = $this->provider->getLogger();
		$message = static::getMessage('ORDER_WITHOUT_SHIPMENT', [
			'#MESSAGE#' => $errorMessage,
		]);

		$logger->warning($message, [
			'AUDIT' => Market\Logger\Trading\Audit::SEND_TRACK,
			'ENTITY_TYPE' => TradingEntity\Registry::ENTITY_TYPE_ORDER,
			'ENTITY_ID' => $this->request->getOrderNumber(),
		]);
	}

	protected function getMarkerCode()
	{
		return $this->provider->getDictionary()->getErrorCode('SEND_TRACK_ERROR');
	}
}