<?php

namespace Yandex\Market\Trading\Service\Marketplace\Action\SendItems;

use Bitrix\Main;
use Yandex\Market;
use Yandex\Market\Trading\Entity as TradingEntity;
use Yandex\Market\Trading\Service as TradingService;

/**
 * @property TradingService\Marketplace\Provider $provider
 * @property Request $request
 */
class Action extends TradingService\Reference\Action\DataAction
{
	use Market\Reference\Concerns\HasMessage;
	use TradingService\Common\Concerns\Action\HasOrder;
	use TradingService\Common\Concerns\Action\HasOrderMarker;
	use TradingService\Common\Concerns\Action\HasItemIdMatch;

	public function __construct(
		TradingService\Marketplace\Provider $provider,
		TradingEntity\Reference\Environment $environment,
		array $data
	)
	{
		parent::__construct($provider, $environment, $data);
	}

	public function getAudit()
	{
		return Market\Logger\Trading\Audit::SEND_ITEMS;
	}

	protected function createRequest(array $data)
	{
		return new Request($data);
	}

	public function process()
	{
		try
		{
			$items = $this->makeItems();

			if ($this->getItemsTotalCount($items) === 0 && $this->isOrderCancelled()) { return; }
			if ($this->request->isAutoSubmit() && !$this->isTotalCountChanged($items)) { return; }

			$this->validateItems($items);
			$this->sendItems($items);
			$this->logItems($items);
			$this->saveTotalCount($items);

			$this->resolveOrderMarker(true);
		}
		catch (Market\Exceptions\Api\Request $exception)
		{
			$this->handleException($exception);
		}
		catch (Market\Exceptions\Trading\Validation $exception)
		{
			$this->handleException($exception);
		}
	}

	protected function handleException($exception)
	{
		$result = new Main\Result();
		$result->addError(new Main\Error($exception->getMessage(), $exception->getCode()));

		$this->resolveOrderMarker(false, $result);
		throw $exception;
	}

	protected function isOrderCancelled()
	{
		$statusValue = $this->getOrderStatus();

		return $this->provider->getStatus()->isCanceled($statusValue);
	}

	protected function getOrderStatus()
	{
		$result = $this->getStoredOrderStatus();

		if ($result === null)
		{
			$result = $this->getExternalOrderStatus();
		}

		return $result;
	}

	protected function getStoredOrderStatus()
	{
		$orderId = $this->request->getOrderId();
		$stored = (string)$this->provider->getStatus()->getStored($orderId);
		$result = null;

		if ($stored !== '')
		{
			list($result) = explode(':', $stored, 2);
		}

		return $result;
	}

	protected function getExternalOrderStatus()
	{
		return $this->getExternalOrder()->getStatus();
	}

	protected function makeItems()
	{
		$items = $this->request->getItems();
		$items = $this->extendItems($items);

		return $items;
	}

	protected function extendItems(array $items)
	{
		$items = $this->extendItemsRatio($items);
		$items = $this->extendItemsId($items);
		$items = $this->extendItemsInstances($items);

		return $items;
	}

	protected function extendItemsRatio(array $items)
	{
		if (!$this->request->isAutoSubmit()) { return $items; }

		$command = new TradingService\Common\Command\OfferPackRatio($this->provider, $this->environment);
		$productIds = array_column($items, 'productId');
		$productIds = array_filter($productIds);

		$packRatio = $command->make($productIds);

		foreach ($items as &$item)
		{
			if (!isset($item['productId'], $packRatio[$item['productId']])) { continue; }

			$ratio = $packRatio[$item['productId']];
			$count = $item['count'] / $ratio;

			$item['ratio'] = $ratio;
			$item['count'] = (int)Market\Data\Quantity::floor($count, 0);
			$item['countExact'] = $count;
		}
		unset($item);

		return $items;
	}

	protected function extendItemsId(array $items)
	{
		foreach ($items as $key => &$item)
		{
			if (isset($item['id'])) { continue; }

			$id = $this->getItemId($item);

			if ($id === null)
			{
				unset($items[$key]);
			}
			else
			{
				$item['id'] = $id;
			}
		}
		unset($item);

		return $items;
	}

	protected function extendItemsInstances(array $items)
	{
		$itemsWithInstances = array_filter($items, static function($item) { return isset($item['instances']); });

		if (!empty($itemsWithInstances)) { return $items; }

		$instances = $this->hasManualInstances()
			? $this->collectExternalInstances()
			: $this->collectBasketInstances($items);

		foreach ($items as &$item)
		{
			if (!isset($instances[$item['id']])) { continue; }

			$item['instances'] = $instances[$item['id']];
		}
		unset($item);

		return $items;
	}

	protected function hasManualInstances()
	{
		$uniqueKey = $this->provider->getUniqueKey();
		$orderId = $this->request->getOrderId();

		return Market\Trading\State\OrderData::getValue($uniqueKey, $orderId, 'CIS_MANUAL') === 'Y';
	}

	protected function collectExternalInstances()
	{
		$result = [];

		/** @var Market\Api\Model\Order\Item */
		foreach ($this->getExternalOrder()->getItems() as $item)
		{
			if (!($item instanceof TradingService\Marketplace\Model\Order\Item)) { continue; }

			$instances = $item->getInstances();

			if ($instances === null) { continue; }

			$result[$item->getId()] = $instances->toArray();
		}

		return $result;
	}

	protected function collectBasketInstances(array $items)
	{
		$order = $this->getOrder();
		$result = [];

		foreach ($items as $item)
		{
			$basketCode = $this->getItemBasketCode($item);

			if ($basketCode === null) { continue; }

			$basketResult = $order->getBasketItemData($basketCode);
			$basketData = $basketResult->getData();

			if (!isset($basketData['MARKING_GROUP']) || (string)$basketData['MARKING_GROUP'] === '') { continue; }
			if (!isset($basketData['INSTANCES']) || !is_array($basketData['INSTANCES'])) { continue; }

			$instances = [];

			foreach ($basketData['INSTANCES'] as $instance)
			{
				$instanceFormatted = [];

				if (isset($instance['CIS']))
				{
					$instanceFormatted['cis'] = Market\Data\Trading\Cis::formatMarkingCode($instance['CIS']);
				}

				if (isset($instance['UIN']))
				{
					$instanceFormatted['uin'] = Market\Data\Trading\Uin::formatMarkingCode($instance['UIN']);
				}

				if (empty($instanceFormatted)) { continue; }

				$instances[] = $instanceFormatted;
			}

			$result[$item['id']] = $instances;
		}

		return $result;
	}

	protected function sanitizeItems(array $items)
	{
		foreach ($items as &$item)
		{
			$item = array_intersect_key($item, [
				'id' => true,
				'count' => true,
				'instances' => true,
			]);

			// count

			$item['count'] = (int)$item['count'];

			// instances

			if (isset($item['instances']))
			{
				if (empty($item['instances']) || $item['count'] === 0)
				{
					unset($item['instances']);
				}
				else if (count($item['instances']) > $item['count'])
				{
					$item['instances'] = array_slice($item['instances'], 0, $item['count']);
				}
			}
		}
		unset($item);

		return $items;
	}

	protected function validateItems(array $items)
	{
		if (!$this->request->isAutoSubmit()) { return; }

		$this->validateItemsPack($items);
		$this->validateItemsTotalCount($items);
	}

	protected function validateItemsPack(array $items)
	{
		foreach ($items as $item)
		{
			if (!isset($item['ratio'])) { continue; }

			if (!Market\Data\Quantity::equal($item['countExact'], $item['count']))
			{
				throw new Market\Exceptions\Trading\Validation(self::getMessage('VALIDATE_PACK_ITEM_COUNT', [
					'#PRODUCT_ID#' => $item['productId'],
				]));
			}
		}
	}

	protected function validateItemsTotalCount(array $items)
	{
		$requested = $this->getItemsTotalCount($items);
		$stored = $this->getTotalCount();

		if ($requested < $stored && $this->hasMissingBasketItems($items))
		{
			throw new Market\Exceptions\Trading\Validation(self::getMessage('VALIDATE_NEW_PRODUCT_ADDITION'));
		}
	}

	protected function hasMissingBasketItems(array $items)
	{
		$order = $this->getOrder();
		$existsCodes = $order->getExistsBasketItemCodes();
		$foundCodes = $this->getItemsBasketCodes($items);
		$missingCodes = array_diff($existsCodes, $foundCodes);
		$result = false;

		foreach ($missingCodes as $basketCode)
		{
			$basketData = $order->getBasketItemData($basketCode)->getData();

			if (isset($basketData['PRICE']) && (int)$basketData['PRICE'] > 0)
			{
				$result = true;
			}
		}

		return $result;
	}

	protected function sendItems(array $items)
	{
		$request = $this->createSendItemsRequest($items);
		$sendResult = $request->send();

		if (!$sendResult->isSuccess())
		{
			$errorMessage = implode(PHP_EOL, $sendResult->getErrorMessages());
			$exceptionMessage = static::getMessage('SEND_ERROR', ['#MESSAGE#' => $errorMessage], $errorMessage);

			throw new Market\Exceptions\Api\Request($exceptionMessage);
		}
	}

	protected function createSendItemsRequest(array $items)
	{
		$items = $this->sanitizeItems($items);
		$options = $this->provider->getOptions();
		$logger = $this->provider->getLogger();
		$result = new TradingService\Marketplace\Api\SendItems\Request();

		$result->setLogger($logger);
		$result->setCampaignId($options->getCampaignId());
		$result->setOauthClientId($options->getOauthClientId());
		$result->setOauthToken($options->getOauthToken()->getAccessToken());
		$result->setOrderId($this->request->getOrderId());
		$result->setItems($items);

		return $result;
	}

	protected function logItems(array $items)
	{
		$logger = $this->provider->getLogger();
		$message = static::getMessage('SEND_LOG', [
			'#EXTERNAL_ID#' => $this->request->getOrderId(),
			'#ITEMS_COUNT#' => $this->getItemsTotalCount($items),
		]);

		$logger->info($message, [
			'AUDIT' => Market\Logger\Trading\Audit::SEND_ITEMS,
			'ENTITY_TYPE' => TradingEntity\Registry::ENTITY_TYPE_ORDER,
			'ENTITY_ID' => $this->request->getOrderNumber(),
		]);
	}

	protected function isTotalCountChanged(array $items)
	{
		$current = $this->getItemsTotalCount($items);
		$stored = $this->getStoredTotalCount();

		return ($stored === null || $current !== $stored);
	}

	protected function getTotalCount()
	{
		$result = $this->getStoredTotalCount();

		if ($result === null)
		{
			$result = $this->getExternalTotalCount();
		}

		return $result;
	}

	protected function getStoredTotalCount()
	{
		$serviceKey = $this->provider->getUniqueKey();
		$orderId = $this->request->getOrderId();
		$stored = Market\Trading\State\OrderData::getValue($serviceKey, $orderId, 'ITEMS_TOTAL');

		return $stored !== null ? (int)$stored : null;
	}

	protected function getExternalTotalCount()
	{
		return (int)$this->getExternalOrder()->getItems()->getTotalCount();
	}

	protected function saveTotalCount(array $items)
	{
		$serviceKey = $this->provider->getUniqueKey();
		$orderId = $this->request->getOrderId();

		Market\Trading\State\OrderData::setValues($serviceKey, $orderId, [
			'ITEMS_TOTAL' => $this->getItemsTotalCount($items),
		]);
	}

	protected function getItemsTotalCount(array $items)
	{
		$counts = array_column($items, 'count');

		return (int)array_sum($counts);
	}

	protected function getMarkerCode()
	{
		return $this->provider->getDictionary()->getErrorCode('SEND_ITEMS_ERROR');
	}
}