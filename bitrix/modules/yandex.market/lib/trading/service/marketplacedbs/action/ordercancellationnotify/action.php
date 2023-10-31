<?php

namespace Yandex\Market\Trading\Service\MarketplaceDbs\Action\OrderCancellationNotify;

use Yandex\Market;
use Bitrix\Main;
use Yandex\Market\Trading\Service as TradingService;
use Yandex\Market\Trading\Entity as TradingEntity;

/**
 * @property Request $request
 */
class Action extends TradingService\Common\Action\HttpAction
	implements TradingService\Reference\Action\HasNotification
{
	use Market\Reference\Concerns\HasMessage;

	/** @var TradingEntity\Reference\Order */
	protected $order;
	protected $changes;

	public function getNotification()
	{
		return new Notification($this->provider, $this->environment);
	}

	protected function createRequest(Main\HttpRequest $request, Main\Server $server)
	{
		return new Request($request, $server);
	}

	public function process()
	{
		if (!$this->isDataChanged())
		{
			$this->collectSuccess();
			return;
		}

		$this->loadOrder();
		$this->fillOrder();

		if ($this->hasChanges())
		{
			$this->updateOrder();
		}

		$this->notify();
		$this->saveData();
		$this->collectSuccess();
	}

	protected function loadOrder()
	{
		$orderRegistry = $this->environment->getOrderRegistry();
		$externalId = $this->request->getOrder()->getId();
		$platform = $this->getPlatform();
		$orderId = $orderRegistry->search($externalId, $platform, false);

		if ($orderId === null)
		{
			$message = static::getMessage('ORDER_NOT_EXISTS', [
				'#EXTERNAL_ID#' => $externalId,
			]);
			throw new Market\Exceptions\Trading\InvalidOperation($message);
		}

		$this->order = $orderRegistry->loadOrder($orderId);
	}

	protected function fillOrder()
	{
		$this->fillCancellationAcceptProperty();
	}

	protected function fillCancellationAcceptProperty()
	{
		$propertyId = (string)$this->provider->getOptions()->getProperty('CANCELLATION_ACCEPT');

		if ($propertyId === '') { return; }

		$fillResult = $this->order->fillProperties([
			$propertyId => Market\Data\Trading\CancellationAccept::WAIT,
		]);
		$fillData = $fillResult->getData();

		if (!empty($fillData['CHANGES']))
		{
			$this->pushChange('PROPERTIES', $fillData['CHANGES']);
		}
	}

	protected function updateOrder()
	{
		$updateResult = $this->order->update();

		Market\Result\Facade::handleException($updateResult);
	}

	protected function notify()
	{
		$this->getNotification()->send([
			'PROVIDER' => $this->provider,
			'ORDER' => $this->order,
			'EXTERNAL_ID' => $this->request->getOrder()->getId(),
		]);
	}

	protected function isDataChanged()
	{
		$uniqueKey = $this->provider->getUniqueKey();
		$orderId = $this->request->getOrder()->getId();
		$stored = Market\Trading\State\OrderData::getValue($uniqueKey, $orderId, 'CANCELLATION_ACCEPT');

		return ($stored !== Market\Data\Trading\CancellationAccept::WAIT);
	}

	protected function saveData()
	{
		$uniqueKey = $this->provider->getUniqueKey();
		$orderId = $this->request->getOrder()->getId();

		Market\Trading\State\OrderData::setValues($uniqueKey, $orderId, [
			'CANCELLATION_ACCEPT' => Market\Data\Trading\CancellationAccept::WAIT,
		]);
	}

	protected function collectSuccess()
	{
		if (Market\Config::getOption('ddos_guard', 'N') === 'Y')
		{
			$this->response->setField('ok', true);
		}
		else
		{
			$this->response->setRaw('');
		}
	}

	protected function pushChange($key, $value)
	{
		$this->changes[$key] = $value;
	}

	protected function hasChanges()
	{
		return !empty($this->changes);
	}

	protected function getChange($key)
	{
		return isset($this->changes[$key]) ? $this->changes[$key] : null;
	}
}