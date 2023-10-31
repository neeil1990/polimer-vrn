<?php

namespace Yandex\Market\Trading\Service\MarketplaceDbs\Action\SendDigital;

use Yandex\Market;
use Bitrix\Main;
use Yandex\Market\Trading\Service as TradingService;

/**
 * @property Request $request
*/
class Action extends TradingService\Reference\Action\DataAction
{
	use Market\Reference\Concerns\HasMessage;
	use TradingService\Common\Concerns\Action\HasOrder;
	use TradingService\Common\Concerns\Action\HasOrderMarker;

	protected function createRequest(array $data)
	{
		return new Request($data);
	}

	public function process()
	{
		try
		{
			$orderId = $this->request->getOrderId();
			$items = $this->request->getItems();

			$this->sendDigitalGoods($orderId, $items);

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

	protected function sendDigitalGoods($orderId, $items)
	{
		$request = $this->createSendDigitalGoodsRequest($orderId, $items);
		$result = $request->send();

		Market\Result\Facade::handleException($result);
	}

	protected function createSendDigitalGoodsRequest($orderId, $items)
	{
		$options = $this->provider->getOptions();
		$logger = $this->provider->getLogger();
		$result = new TradingService\MarketplaceDbs\Api\DeliverDigitalGoods\Request();

		$result->setLogger($logger);
		$result->setCampaignId($options->getCampaignId());
		$result->setOauthClientId($options->getOauthClientId());
		$result->setOauthToken($options->getOauthToken()->getAccessToken());
		$result->setOrderId($orderId);
		$result->setItems($this->sanitizeDigitalGoodsItems($items));

		return $result;
	}

	protected function sanitizeDigitalGoodsItems($items)
	{
		foreach ($items as &$item)
		{
			if (isset($item['activate_till']))
			{
				$activateTill = Market\Data\Date::sanitize($item['activate_till']);

				if ($activateTill === null) { continue; }

				$item['activate_till'] = Market\Data\Date::convertForService($activateTill, 'Y-m-d');
			}
		}
		unset($item);

		return $items;
	}

	protected function getMarkerCode()
	{
		return $this->provider->getDictionary()->getErrorCode('SEND_DIGITAL_ERROR');
	}
}