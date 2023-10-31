<?php

namespace Yandex\Market\Trading\Service\MarketplaceDbs\Action\FillPhone;

use Bitrix\Main;
use Yandex\Market;
use Yandex\Market\Trading\Service as TradingService;

class Action extends TradingService\Reference\Action\DataAction
{
	use TradingService\Common\Concerns\Action\HasOrder;
	use TradingService\Common\Concerns\Action\HasOrderMarker;
	use TradingService\Common\Concerns\Action\HasMeaningfulProperties;
	use TradingService\Common\Concerns\Action\HasChangesTrait;

	/** @var TradingService\MarketplaceDbs\Provider */
	protected $provider;
	/** @var TradingService\MarketplaceDbs\Model\Order\Buyer $buyer */
	protected $buyer;

	protected function createRequest(array $data)
	{
		return new Request($data);
	}

	public function process()
	{
		try
		{
			if ($this->isOrderFinished()) { return; }

			$this->loadBuyer();

			$this->fillData();
			$this->fillProperties();

			if ($this->hasChanges())
			{
				$this->resolveOrderMarker(true);
				$this->updateOrder();
			}
		}
		catch (Market\Exceptions\Api\Request $exception)
		{
			if ($this->isExceptionForNonProcessingOrder($exception)) { return; }

			$sendResult = new Main\Result();
			$sendResult->addError(new Main\Error(
				$exception->getMessage(),
				$exception->getCode()
			));

			$this->resolveOrderMarker(false, $sendResult);
			throw $exception;
		}
	}

	protected function isOrderFinished()
	{
		$statusService = $this->provider->getStatus();
		$stored = explode(':', (string)$statusService->getStored($this->request->getOrderId()));

		if ($stored[0] === '') { return false; }

		return $statusService->isCanceled($stored[0], $stored[1]) || $statusService->isOrderDelivered($stored[0]);
	}

	protected function loadBuyer()
	{
		$options = $this->provider->getOptions();
		$logger = $this->provider->getLogger();
		$request = new TradingService\MarketplaceDbs\Api\Buyer\Request();

		$request->setLogger($logger);
		$request->setCampaignId($options->getCampaignId());
		$request->setOauthClientId($options->getOauthClientId());
		$request->setOauthToken($options->getOauthToken()->getAccessToken());
		$request->setOrderId($this->request->getOrderId());

		$send = $request->send();

		Market\Result\Facade::handleException($send, Market\Exceptions\Api\Request::class);

		/** @var TradingService\MarketplaceDbs\Api\Buyer\Response $response */
		$response = $send->getResponse();

		$this->buyer = $response->getBuyer();
	}

	protected function fillData()
	{
		$uniqueKey = $this->provider->getUniqueKey();
		$orderId = $this->request->getOrderId();

		Market\Trading\State\OrderData::setValue($uniqueKey, $orderId, 'PHONE', $this->buyer->getPhone());
	}

	protected function fillProperties()
	{
		$this->setMeaningfulPropertyValues([
			'PHONE' => $this->buyer->getPhone(),
		]);
	}

	protected function isExceptionForNonProcessingOrder(Market\Exceptions\Api\Request $exception)
	{
		return $exception->getMessage() === 'Order is not in shop-processing status.';
	}

	protected function getMarkerCode()
	{
		return $this->provider->getDictionary()->getErrorCode('FILL_PHONE');
	}
}