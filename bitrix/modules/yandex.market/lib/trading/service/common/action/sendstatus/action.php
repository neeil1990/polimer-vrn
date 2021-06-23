<?php

namespace Yandex\Market\Trading\Service\Common\Action\SendStatus;

use Yandex\Market;
use Bitrix\Main;
use Yandex\Market\Trading\Entity as TradingEntity;
use Yandex\Market\Trading\Service as TradingService;

class Action extends TradingService\Reference\Action\DataAction
{
	use Market\Reference\Concerns\HasLang;

	/** @var TradingService\Common\Provider */
	protected $provider;
	/** @var Request */
	protected $request;
	/** @var TradingEntity\Reference\Order */
	protected $order;

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
			$isStateReached = ($sendResult->isSuccess() || $this->checkHasStatus($orderId, $statusOut));

			$this->resolveOrderMarker($isStateReached, $sendResult);

			if ($isStateReached)
			{
				$this->logState($orderId, $statusOut);
				$this->saveState($orderId, $statusOut);
			}
			else
			{
				$errorMessage = implode(PHP_EOL, $sendResult->getErrorMessages());
				throw new Market\Exceptions\Api\Request($errorMessage);
			}
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
			list($status, $subStatus) = $this->getExternalStatus($state);
			Market\Api\Model\OrderFacade::submitStatus($options, $orderId, $status, $subStatus, $logger);
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

	protected function checkHasStatus($orderId, $state)
	{
		return false;
	}

	protected function loadExternalOrder($orderId)
	{
		$options = $this->provider->getOptions();

		return Market\Api\Model\OrderFacade::load($options, $orderId);
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

	protected function resolveOrderMarker($isStateReached, Main\Result $sendResult)
	{
		try
		{
			if ($this->isExistOrderMarker() === $isStateReached)
			{
				if ($isStateReached)
				{
					$order = $this->getOrder();

					$this->unmarkOrder($order);
					$this->updateOrder($order);
				}
				else if (!$this->request->getImmediate())
				{
					$order = $this->getOrder();

					$this->markOrder($order, $sendResult);
					$this->updateOrder($order);
				}
			}
		}
		catch (Main\SystemException $exception)
		{
			$logger = $this->provider->getLogger();
			$logger->error($exception, [
				'AUDIT' => $this->getAudit(),
				'ENTITY_TYPE' => TradingEntity\Registry::ENTITY_TYPE_ORDER,
				'ENTITY_ID' => $this->request->getOrderNumber(),
			]);
		}
	}

	protected function isExistOrderMarker()
	{
		$orderRegistry = $this->environment->getOrderRegistry();
		$orderId = $this->request->getInternalId();
		$code = $this->getMarkerCode();

		return $orderRegistry->isExistMarker($orderId, $code);
	}

	protected function getOrder()
	{
		if ($this->order === null)
		{
			$this->order = $this->loadOrder();
		}

		return $this->order;
	}

	protected function loadOrder()
	{
		$orderId = $this->request->getInternalId();
		$orderRegistry = $this->environment->getOrderRegistry();

		return $orderRegistry->loadOrder($orderId);
	}

	protected function unmarkOrder(TradingEntity\Reference\Order $order)
	{
		$code = $this->getMarkerCode();
		$removeResult = $order->removeMarker($code);

		Market\Result\Facade::handleException($removeResult);
	}

	protected function markOrder(TradingEntity\Reference\Order $order, Main\Result $result)
	{
		$message = implode(PHP_EOL, $result->getErrorMessages());
		$code = $this->getMarkerCode();

		$addResult = $order->addMarker($message, $code);

		Market\Result\Facade::handleException($addResult);
	}

	protected function getMarkerCode()
	{
		return $this->provider->getDictionary()->getErrorCode('SEND_STATUS_ERROR');
	}

	protected function updateOrder(TradingEntity\Reference\Order $order)
	{
		$updateResult = $order->update();

		Market\Result\Facade::handleException($updateResult);
	}

	protected function getStatusOut()
	{
		$requestStatus = $this->request->getStatus();

		return (string)$this->provider->getOptions()->getStatusOut($requestStatus);
	}
}