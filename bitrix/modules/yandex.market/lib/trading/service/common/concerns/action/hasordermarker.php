<?php

namespace Yandex\Market\Trading\Service\Common\Concerns\Action;

use Bitrix\Main;
use Yandex\Market;
use Yandex\Market\Trading\Entity as TradingEntity;
use Yandex\Market\Trading\Service as TradingService;

/**
 * trait HasOrderMarker
 * @method string getAudit()
 * @method TradingEntity\Reference\Order getOrder()
 * @method void updateOrder(TradingEntity\Reference\Order $order = null)
 * @property TradingService\Common\Provider $provider
 * @property TradingEntity\Reference\Environment $environment
 * @property TradingService\Common\Action\SendRequest $request
 */
trait HasOrderMarker
{
	protected function resolveOrderMarker($isStateReached, Main\Result $sendResult = null)
	{
		try
		{
			if ($this->isExistOrderMarker() !== $isStateReached) { return; }

			if ($isStateReached)
			{
				$order = $this->getOrder();

				$this->unmarkOrder($order);
				$this->updateOrder($order);
			}
			else if (!$this->request->getImmediate())
			{
				Market\Reference\Assert::notNull($sendResult, 'sendResult');

				$order = $this->getOrder();

				$this->markOrder($order, $sendResult);
				$this->updateOrder($order);
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
		throw new Main\NotImplementedException('Action::getMarkerCode');
	}
}