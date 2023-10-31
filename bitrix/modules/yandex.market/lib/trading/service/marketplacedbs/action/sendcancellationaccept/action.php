<?php

namespace Yandex\Market\Trading\Service\MarketplaceDbs\Action\SendCancellationAccept;

use Bitrix\Main;
use Yandex\Market;
use Yandex\Market\Trading\Entity as TradingEntity;
use Yandex\Market\Trading\Service as TradingService;

/** @property Request $request */
class Action extends TradingService\Reference\Action\DataAction
	implements
		TradingService\Reference\Action\HasActivity
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
		return Market\Logger\Trading\Audit::SEND_CANCELLATION_ACCEPT;
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
			$accepted = $this->request->isAccepted();
			$reason = $accepted ? null : $this->request->getReason();

			if (!$this->isChanged($orderId, $accepted)) { return; }

			$this->sendCancellationAccept($orderId, $accepted, $reason);
			$this->logCancellationAccept($orderId, $accepted, $reason);

			$this->saveData($orderId, $accepted);
			$this->resolveOrderMarker(true);
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

	protected function isChanged($orderId, $accepted)
	{
		$uniqueKey = $this->provider->getUniqueKey();
		$stored = Market\Trading\State\OrderData::getValue($uniqueKey, $orderId, 'CANCELLATION_ACCEPT');
		$expected = $this->getDataCancellationAcceptState($accepted);

		return $stored !== $expected;
	}

	protected function sendCancellationAccept($orderId, $accepted, $reason = null)
	{
		$request = $this->createSendCancellationAcceptRequest($orderId, $accepted, $reason);
		$sendResult = $request->send();

		if (!$sendResult->isSuccess())
		{
			$errorMessage = implode(PHP_EOL, $sendResult->getErrorMessages());
			$exceptionMessage = static::getMessage('SEND_ERROR', [ '#MESSAGE#' => $errorMessage ], $errorMessage);

			throw new Market\Exceptions\Api\Request($exceptionMessage);
		}
	}

	protected function createSendCancellationAcceptRequest($orderId, $accepted, $reason = null)
	{
		$options = $this->provider->getOptions();
		$logger = $this->provider->getLogger();
		$result = new TradingService\MarketplaceDbs\Api\SendCancellationAccept\Request();

		$result->setLogger($logger);
		$result->setCampaignId($options->getCampaignId());
		$result->setOauthClientId($options->getOauthClientId());
		$result->setOauthToken($options->getOauthToken()->getAccessToken());
		$result->setOrderId($orderId);
		$result->setAccepted($accepted);
		$result->setReason($reason);

		return $result;
	}

	protected function logCancellationAccept($orderId, $accepted, $reason)
	{
		$logger = $this->provider->getLogger();
		$stateSuffix = $accepted ? 'ACCEPTED' : 'REJECTED';
		$message = static::getMessage('SEND_LOG_' . $stateSuffix, [
			'#EXTERNAL_ID#' => $orderId,
			'#REASON#' => $reason,
		]);

		$logger->info($message, [
			'AUDIT' => Market\Logger\Trading\Audit::SEND_CANCELLATION_ACCEPT,
			'ENTITY_TYPE' => TradingEntity\Registry::ENTITY_TYPE_ORDER,
			'ENTITY_ID' => $this->request->getOrderNumber(),
		]);
	}

	protected function saveData($orderId, $accepted)
	{
		$uniqueKey = $this->provider->getUniqueKey();

		Market\Trading\State\OrderData::setValues($uniqueKey, $orderId, [
			'CANCELLATION_ACCEPT' => $this->getDataCancellationAcceptState($accepted),
		]);
	}

	protected function getDataCancellationAcceptState($accepted)
	{
		return $accepted
			? Market\Data\Trading\CancellationAccept::CONFIRM
			: Market\Data\Trading\CancellationAccept::REJECT;
	}

	protected function getMarkerCode()
	{
		return $this->provider->getDictionary()->getErrorCode('SEND_CANCELLATION_ACCEPT_ERROR');
	}
}