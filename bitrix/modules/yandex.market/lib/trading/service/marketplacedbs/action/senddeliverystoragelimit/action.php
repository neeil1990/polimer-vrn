<?php

namespace Yandex\Market\Trading\Service\MarketplaceDbs\Action\SendDeliveryStorageLimit;

use Bitrix\Main;
use Yandex\Market;
use Yandex\Market\Trading\Entity as TradingEntity;
use Yandex\Market\Trading\Service as TradingService;

/** @property Request $request */
class Action extends TradingService\Reference\Action\DataAction
	implements TradingService\Reference\Action\HasActivity
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
		return Market\Logger\Trading\Audit::SEND_DELIVERY_STORAGE_LIMIT;
	}

	public function getActivity()
	{
		return new Activity($this->provider, $this->environment);
	}

	protected function createRequest(array $data)
	{
		return new Request($data);
	}

	public function process()
	{
		try
		{
			$orderId = $this->request->getOrderId();
			$date = $this->request->getNewDate();

			if (!$this->isChanged($orderId, $date)) { return; }

			$this->sendDeliveryStorageLimit($orderId, $date);
			$this->logDeliveryStorageLimit($date);

			$this->saveData($orderId, $date);
			$this->resolveOrderMarker(true);
			$this->flushCache($orderId);
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

	protected function isChanged($orderId, Main\Type\Date $date)
	{
		$uniqueKey = $this->provider->getUniqueKey();
		$stored = Market\Trading\State\OrderData::getValue($uniqueKey, $orderId, 'DELIVERY_STORAGE_LIMIT');
		$expected = $this->formatDataDate($date);

		return $stored !== $expected;
	}

	protected function sendDeliveryStorageLimit($orderId, Main\Type\Date $date)
	{
		$request = $this->createSendDeliveryDateRequest($orderId, $date);
		$sendResult = $request->send();

		if ($sendResult->isSuccess()) { return; }

		$sendErrors = $sendResult->getErrors();
		$sendErrors = $this->filterErrorsDeliveryDate($sendErrors, $date);

		if (!empty($sendErrors))
		{
			$errorMessages = array_map(static function ($error) { return $error->getMessage(); }, $sendErrors);
			$errorMessage = implode(PHP_EOL, $errorMessages);
			$exceptionMessage = static::getMessage('SEND_ERROR', [ '#MESSAGE#' => $errorMessage ], $errorMessage);

			throw new Market\Exceptions\Api\Request($exceptionMessage);
		}
	}

	protected function filterErrorsDeliveryDate($errors, Main\Type\Date $date)
	{
		if (!$this->request->isAutoSubmit()) { return $errors; }

		$skip = array_flip([
			sprintf('New storage limit date should be after then current. new:%1$s, current:%1$s', $date->format('Y-m-d')),
		]);

		foreach ($errors as $key => $error)
		{
			$message = $error->getMessage();

			if (isset($skip[$message]))
			{
				unset($errors[$key]);
			}
		}

		return $errors;
	}

	protected function createSendDeliveryDateRequest($orderId, Main\Type\Date $date)
	{
		$options = $this->provider->getOptions();
		$logger = $this->provider->getLogger();
		$result = new TradingService\MarketplaceDbs\Api\SendDeliveryStorageLimit\Request();

		$result->setLogger($logger);
		$result->setCampaignId($options->getCampaignId());
		$result->setOauthClientId($options->getOauthClientId());
		$result->setOauthToken($options->getOauthToken()->getAccessToken());
		$result->setOrderId($orderId);
		$result->setNewDate($date);

		return $result;
	}

	protected function logDeliveryStorageLimit(Main\Type\Date $date)
	{
		$logger = $this->provider->getLogger();
		$dateFormatted = Market\Data\Date::format($date);
		$message = static::getMessage('SEND_LOG', [ '#DATE#' => $dateFormatted ], $dateFormatted);

		$logger->info($message, [
			'AUDIT' => Market\Logger\Trading\Audit::SEND_DELIVERY_STORAGE_LIMIT,
			'ENTITY_TYPE' => TradingEntity\Registry::ENTITY_TYPE_ORDER,
			'ENTITY_ID' => $this->request->getOrderNumber(),
		]);
	}

	protected function saveData($orderId, $accepted)
	{
		$uniqueKey = $this->provider->getUniqueKey();

		Market\Trading\State\OrderData::setValues($uniqueKey, $orderId, [
			'DELIVERY_STORAGE_LIMIT' => $this->formatDataDate($accepted),
		]);
	}

	protected function formatDataDate(Main\Type\Date $date)
	{
		return $date->format(Market\Data\Date::FORMAT_DEFAULT_SHORT);
	}

	protected function getMarkerCode()
	{
		return $this->provider->getDictionary()->getErrorCode('SEND_DELIVERY_STORAGE_LIMIT_ERROR');
	}

	protected function flushCache($orderId)
	{
		Market\Trading\State\SessionCache::release('order', $orderId);
	}
}