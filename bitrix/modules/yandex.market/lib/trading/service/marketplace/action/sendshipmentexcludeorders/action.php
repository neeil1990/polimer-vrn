<?php

namespace Yandex\Market\Trading\Service\Marketplace\Action\SendShipmentExcludeOrders;

use Yandex\Market;
use Yandex\Market\Trading\Entity as TradingEntity;
use Yandex\Market\Trading\Service as TradingService;

/**
 * @property TradingService\Marketplace\Provider $provider
 * @property Request $request
 */
class Action extends TradingService\Reference\Action\DataAction
	implements
		TradingService\Reference\Action\HasActivity
{
	use Market\Reference\Concerns\HasMessage;

	public function getAudit()
	{
		return Market\Logger\Trading\Audit::SEND_SHIPMENT_EXCLUDE_ORDERS;
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
		$this->sendExcludeOrders();
		$this->logExcludeOrders();
	}

	protected function sendExcludeOrders()
	{
		$request = $this->buildRequest();
		$sendResult = $request->send();

		if (!$sendResult->isSuccess())
		{
			$message = self::getMessage('RESPONSE_FAIL', [
				'#MESSAGE#' => implode(PHP_EOL, $sendResult->getErrorMessages())
			]);
			throw new Market\Exceptions\Api\Request($message);
		}
	}

	protected function buildRequest()
	{
		$result = new TradingService\Marketplace\Api\ShipmentExcludeOrders\Request();
		$options = $this->provider->getOptions();
		$logger = $this->provider->getLogger();

		$result->setLogger($logger);
		$result->setOauthClientId($options->getOauthClientId());
		$result->setOauthToken($options->getOauthToken()->getAccessToken());
		$result->setCampaignId($options->getCampaignId());
		$result->setShipmentId($this->request->getShipmentId());
		$result->setOrderIds($this->request->getOrderIds());

		return $result;
	}

	protected function logExcludeOrders()
	{
		$logger = $this->provider->getLogger();
		$orderIds = $this->request->getOrderIds();
		$orderMap = $this->findOrderNumbers($orderIds);
		$orderMap += array_fill_keys($orderIds, null);

		foreach ($orderMap as $orderId => $orderNumber)
		{
			$message = self::getMessage('EXCLUDE_LOG', [
				'#SHIPMENT_ID#' => $this->request->getShipmentId(),
			]);

			$logger->info($message, [
				'AUDIT' => $this->getAudit(),
				'ENTITY_TYPE' => TradingEntity\Registry::ENTITY_TYPE_ORDER,
				'ENTITY_ID' => $orderNumber ?: $orderId,
			]);
		}
	}

	protected function findOrderNumbers(array $orderIds)
	{
		return $this->environment->getOrderRegistry()->searchList($orderIds, $this->getPlatform());
	}
}