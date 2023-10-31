<?php

namespace Yandex\Market\Trading\Service\Marketplace\Action\OrderAccept;

use Yandex\Market;
use Bitrix\Main;
use Yandex\Market\Trading\Entity as TradingEntity;
use Yandex\Market\Trading\Service as TradingService;

/** @property TradingService\Marketplace\Provider $provider */
class Action extends TradingService\Common\Action\OrderAccept\Action
{
	use TradingService\Marketplace\Concerns\Action\HasBasketStoreData;
	use TradingService\Marketplace\Concerns\Action\HasBasketWarehouses;

	/** @var Request */
	protected $request;

	protected function createRequest(Main\HttpRequest $request, Main\Server $server)
	{
		return new Request($request, $server);
	}

	protected function getOrderNum()
	{
		if ($this->provider->getOptions()->useAccountNumberTemplate())
		{
			return $this->order->getId();
		}

		return parent::getOrderNum();
	}

	protected function fillOrder()
	{
		parent::fillOrder();
		$this->fillContact();
	}

	protected function fillProperties()
	{
		parent::fillProperties();
		$this->fillBuyerProperties();
	}

	protected function fillBuyerProperties()
	{
		$buyer = $this->request->getOrder()->getBuyer();

		if ($buyer === null) { return null; }

		$this->setMeaningfulPropertyValues($buyer->getMeaningfulValues());
	}

	protected function fillDelivery()
	{
		$deliveryId = $this->provider->getOptions()->getDeliveryId();

		if ($deliveryId !== '')
		{
			$this->order->createShipment($deliveryId);
		}
	}

	protected function fillBasketStore()
	{
		$options = $this->provider->getOptions();

		if ($options->useWarehouses())
		{
			$items = $this->request->getOrder()->getItems();
			$basketMap = $this->getBasketItemsMap($items);
			$itemWarehouseMap = $this->makeBasketWarehouseMap($items);
			$warehouseIds = array_unique(array_values($itemWarehouseMap));
			$warehouseStoreMap = $this->mapWarehouseStores($warehouseIds);
			$basketStoreMap = $this->combineBasketStoreMap($basketMap, $itemWarehouseMap, $warehouseStoreMap);

			foreach ($basketStoreMap as $basketCode => $storeId)
			{
				$this->order->setBasketItemStore($basketCode, $storeId);
			}
		}
		else
		{
			$this->order->setBasketStore($this->provider->getOptions()->getProductSelfStores());
		}
	}

	protected function makeBasketWarehouseMap(TradingService\Marketplace\Model\Order\ItemCollection $items)
	{
		$result = [];

		/** @var TradingService\Marketplace\Model\Order\Item $item */
		foreach ($items as $item)
		{
			$result[$item->getInternalId()] = $item->getPartnerWarehouseId();
		}

		return $result;
	}

	protected function mapWarehouseStores(array $warehouseIds)
	{
		$options = $this->provider->getOptions();
		$storeEntity = $this->environment->getStore();
		$warehouseField = $options->getWarehouseStoreField();
		$result = [];

		foreach ($warehouseIds as $warehouseId)
		{
			$storeId = $storeEntity->findStore($warehouseField, $warehouseId);

			if ($storeId === null) { continue; }

			$result[$warehouseId] = $storeId;
		}

		return $result;
	}

	protected function combineBasketStoreMap(array $basketMap, array $itemWarehouseMap, array $warehouseStoreMap)
	{
		$result = [];

		foreach ($basketMap as $itemId => $basketCode)
		{
			if (!isset($itemWarehouseMap[$itemId])) { continue; }

			$warehouseId = $itemWarehouseMap[$itemId];

			if (!isset($warehouseStoreMap[$warehouseId])) { continue; }

			$result[$basketCode] = $warehouseStoreMap[$warehouseId];
		}

		return $result;
	}

	protected function fillPaySystem()
	{
		$this->fillPaySystemSubsidy();
		$this->fillPaySystemCommon();
	}

	protected function fillPaySystemSubsidy()
	{
		$options = $this->provider->getOptions();
		$subsidySystemId = $options->getSubsidyPaySystemId();

		if ($subsidySystemId !== '' && $options->includeBasketSubsidy())
		{
			$subsidySum = $this->calculateSubsidySum();

			if ($subsidySum > 0)
			{
				$this->order->createPayment($subsidySystemId, $subsidySum, [
					'SUBSIDY' => true,
					'ORDER_ID' => $this->request->getOrder()->getId(),
				]);
			}
		}
	}

	protected function calculateSubsidySum()
	{
		return $this->request->getOrder()->getItems()->getSubsidySum();
	}

	protected function fillPaySystemCommon()
	{
		$paySystemId = $this->resolvePaySystem();

		if ($paySystemId !== '')
		{
			$this->order->createPayment($paySystemId);
		}
	}

	protected function resolvePaySystem()
	{
		$paymentType = $this->request->getOrder()->getPaymentType();

		return $this->provider->getOptions()->getPaySystemId($paymentType);
	}

	protected function getItemPrice(Market\Api\Model\Order\Item $item)
	{
		if ($this->provider->getOptions()->includeBasketSubsidy())
		{
			$result = $item->getPrice() + $item->getSubsidy();
		}
		else
		{
			$result = $item->getPrice();
		}

		return $result;
	}

	protected function fillContact()
	{
		try
		{
			$command = new TradingService\Common\Command\AnonymousContact($this->provider, $this->environment);
			$contacts = $command->execute();

			$this->order->fillContacts($contacts);
		}
		catch (Main\SystemException $exception)
		{
			$this->provider->getLogger()->warning($exception);
		}
	}

	protected function makeData()
	{
		return
			$this->makeFakeData()
			+ $this->makeShipmentData();
	}

	protected function makeFakeData()
	{
		if (!$this->request->getOrder()->isFake()) { return []; }

		return [
			'FAKE' => 'Y',
		];
	}

	protected function makeShipmentData()
	{
		$shipmentDates = $this->request->getOrder()->getMeaningfulShipmentDates();

		if (empty($shipmentDates)) { return []; }

		/** @var Main\Type\DateTime $shipmentDate */
		$shipmentDate = reset($shipmentDates);

		return [
			'SHIPMENT_DATE' => $shipmentDate->format(Market\Data\DateTime::FORMAT_DEFAULT_FULL),
		];
	}
}