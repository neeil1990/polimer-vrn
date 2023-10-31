<?php

namespace Yandex\Market\Trading\Service\Common\Action\SendStatus;

use Yandex\Market;
use Bitrix\Main;
use Yandex\Market\Trading\Entity as TradingEntity;
use Yandex\Market\Trading\Service as TradingService;

class Action extends TradingService\Reference\Action\DataAction
{
	use Market\Reference\Concerns\HasLang;
	use TradingService\Common\Concerns\Action\HasOrder;
	use TradingService\Common\Concerns\Action\HasOrderMarker;

	/** @var TradingService\Common\Provider */
	protected $provider;
	/** @var Request */
	protected $request;

	protected static function includeMessages()
	{
		Main\Localization\Loc::loadMessages(__FILE__);
	}

	public function __construct(TradingService\Common\Provider $provider, TradingEntity\Reference\Environment $environment, array $data)
	{
		parent::__construct($provider, $environment, $data);
	}

	protected function createRequest(array $data)
	{
		return new Request($data);
	}

	public function getAudit()
	{
		return Market\Logger\Trading\Audit::SEND_STATUS;
	}

	public function process()
	{
		$statusOut = $this->getStatusOut();
		$orderId = $this->request->getOrderId();

		if ($statusOut !== '' && $this->isChangedOrderStatus($orderId, $statusOut))
		{
			$sendResult = $this->sendStatus($orderId, $statusOut);
			$isStateReached = (
				$sendResult->isSuccess()
				|| $this->fixStatus($sendResult, $orderId, $statusOut)
				|| $this->checkHasStatus($orderId, $statusOut)
			);

			$this->resolveOrderMarker($isStateReached, $sendResult);

			if (!$isStateReached)
			{
				$errorMessage = implode(PHP_EOL, $sendResult->getErrorMessages());
				throw new Market\Exceptions\Api\Request($errorMessage);
			}

			$this->logState($orderId, $statusOut);
			$this->saveState($orderId, $statusOut);
			$this->saveData($orderId);
			$this->finalize($orderId, $statusOut);
		}
	}

	protected function isChangedOrderStatus($orderId, $state)
	{
		list($status, $substatus) = $this->getExternalStatus($state);

		return $this->provider->getStatus()->isChanged($orderId, $status, $substatus);
	}

	protected function sendStatus($orderId, $state)
	{
		$options = $this->provider->getOptions();
		$logger = $this->provider->getLogger();
		$result = new Main\Result();

		try
		{
			$orderFacade = $this->provider->getModelFactory()->getOrderFacadeClassName();
			list($status, $subStatus) = $this->getExternalStatus($state);
			$payload = $this->getExternalPayload($status, $subStatus);

			$this->externalOrder = $orderFacade::submitStatus($options, $orderId, $status, $subStatus, $logger, $payload);
		}
		catch (Market\Exceptions\Api\Request $exception)
		{
			$error = new Main\Error($exception->getMessage());
			$result->addError($error);
		}

		return $result;
	}

	protected function getExternalStatus($state)
	{
		return [ $state, null ];
	}

	protected function getExternalPayload($status, $subStatus)
	{
		return [];
	}

	protected function fixStatus(Main\Result $sendResult, $orderId, $state)
	{
		return false;
	}

	protected function checkHasStatus($orderId, $state)
	{
		return false;
	}

	protected function logState($orderId, $state)
	{
		list($status, $subStatus) = $this->getExternalStatus($state);

		$logger = $this->provider->getLogger();
		$message = static::getLang('TRADING_ACTION_SEND_STATUS_SEND_LOG', [
			'#EXTERNAL_ID#' => $orderId,
			'#STATUS#' => $status,
			'#SUBSTATUS#' => $subStatus,
		]);

		$logger->info($message, [
			'AUDIT' => Market\Logger\Trading\Audit::SEND_STATUS,
			'ENTITY_TYPE' => TradingEntity\Registry::ENTITY_TYPE_ORDER,
			'ENTITY_ID' => $this->request->getOrderNumber(),
		]);
	}

	protected function saveState($orderId, $state)
	{
		$serviceKey = $this->provider->getUniqueKey();
		$fullStatus = $this->getExternalStatus($state);

		Market\Trading\State\OrderStatus::setValue($serviceKey, $orderId, implode(':', $fullStatus));
		Market\Trading\State\OrderStatus::commit($serviceKey, $orderId);
	}

	protected function finalize($orderId, $state)
	{
		// nothing by default
	}

	protected function saveData($orderId)
	{
		$serviceKey = $this->provider->getUniqueKey();
		$data = $this->makeData();

		Market\Trading\State\OrderData::setValues($serviceKey, $orderId, $data);
	}

	protected function makeData()
	{
		return [];
	}

	protected function getMarkerCode()
	{
		return $this->provider->getDictionary()->getErrorCode('SEND_STATUS_ERROR');
	}

	protected function getStatusOut()
	{
		$externalStatus = $this->request->getExternalStatus();

		if ($externalStatus !== null)
		{
			$result = $externalStatus;
		}
		else
		{
			$requestStatus = $this->request->getStatus();

			$result = (string)$this->provider->getOptions()->getStatusOut($requestStatus);
		}

		return $result;
	}
}