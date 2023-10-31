<?php

namespace Yandex\Market\Trading\Service\Marketplace\Action\PushStocks;

use Bitrix\Main;
use Yandex\Market;
use Yandex\Market\Trading\Service as TradingService;

/**
 * @property TradingService\Marketplace\Provider $provider
 * @property Request $request
*/
class Action extends TradingService\Reference\Action\DataAction
{
	use Market\Reference\Concerns\HasMessage;

	protected $pushStore;
	protected $warehouseMap;
	protected $warehouseCampaignId;

	protected function createRequest(array $data)
	{
		return new Request($data);
	}

	public function process()
	{
		$productIds = $this->getProducts();

		if (empty($productIds))
		{
			$this->finalize();
			return;
		}

		$this->collectNext($productIds);

		$pushStore = $this->getPushStore();
		list($exportedIds, $deletedIds) = $this->feedSplit($productIds);
		$amounts = $this->getAmounts($exportedIds);
		$amounts = $this->applyEmulatedUpdated($amounts);
		$amounts = $this->applyReserves($amounts);
		$amounts = $this->applyPackRatio($amounts);
		$amounts = $this->applyMissing($amounts, $exportedIds);
		$amounts = $this->applyDeleted($amounts, $deletedIds);
		$amounts = $this->applyAmountsSku($amounts);
		$amounts = $this->extendPushed($amounts, $productIds);
		$chunkSize = $this->getSkusChunkSize();

		if (!$this->request->isForce())
		{
			list($amounts, $unchanged) = $pushStore->splitChanged($amounts);

			$pushStore->touch($unchanged);
		}

		foreach (array_chunk($amounts, $chunkSize) as $amountsChunk)
		{
			$skus = $this->buildSkus($amountsChunk);

			$this->sendSkus($skus);
			$pushStore->commit($amountsChunk);
		}

		if (!$this->response->getField('hasNext'))
		{
			$this->finalize();
		}
	}

	protected function finalize()
	{
		$action = $this->request->getAction();
		$timestamp = $this->request->getTimestamp();

		if ($action !== Market\Trading\State\PushAgent::ACTION_REFRESH || $timestamp === null) { return; }

		$pushStore = $this->getPushStore();
		$untouched = $pushStore->untouched($timestamp, [ '>VALUE' => 0 ]);
		$chunkSize = $this->getSkusChunkSize();

		foreach (array_chunk($untouched, $chunkSize) as $amountsChunk)
		{
			$filtered = $this->filterMatchedWarehouse($amountsChunk);
			$skus = $this->emulateSkus($filtered);

			$this->sendSkus($skus);
			$pushStore->release($amountsChunk);
		}
	}

	protected function getProducts()
	{
		$warehouseMap = $this->getWarehouseMap();
		$stores = !empty($warehouseMap) ? array_merge(...array_values($warehouseMap)) : [];
		$timestamp = null;

		if ($this->request->getAction() !== Market\Trading\State\PushAgent::ACTION_REFRESH)
		{
			$timestamp = $this->request->getTimestamp();
		}

		return $this->environment->getStore()->getChanged(
			$stores,
			$timestamp,
			$this->request->getOffset(),
			$this->request->getLimit()
		);
	}

	protected function feedSplit($productIds)
	{
		$command = new TradingService\Marketplace\Command\FeedExists(
			$this->provider,
			$this->environment
		);

		return $command->splitProducts($productIds);
	}

	protected function collectNext($productIds)
	{
		$offset = $this->request->getOffset();
		$limit = $this->request->getLimit();
		$found = count($productIds);

		if ($found < $limit) { return; }

		$this->response->setField('hasNext', true);
		$this->response->setField('offset', $offset + $limit);
	}

	protected function getAmounts($productIds)
	{
		$resultParts = [];

		foreach ($this->getWarehouseMap() as $warehouseId => $stores)
		{
			$amounts = $this->environment->getStore()->getAmounts($stores, $productIds);

			foreach ($amounts as &$amount)
			{
				$amount['WAREHOUSE_ID'] = $warehouseId;
			}
			unset($amount);

			$resultParts[] = $amounts;
		}

		return !empty($resultParts) ? array_merge(...$resultParts) : [];
	}

	protected function getWarehouseMap()
	{
		if ($this->warehouseMap === null)
		{
			$this->warehouseMap = $this->buildWarehouseMap();
		}

		return $this->warehouseMap;
	}

	protected function buildWarehouseMap()
	{
		$options = $this->provider->getOptions();
		$result = [];

		if ($options->useWarehouses())
		{
			$primaryField = $options->getWarehousePrimaryField();
			$storeService = $this->environment->getStore();
			$storesMap = $storeService->existsStores($primaryField);

			foreach ($storesMap as $storeId => $warehouseId)
			{
				if (!isset($result[$warehouseId]))
				{
					$result[$warehouseId] = [ $storeId ];
				}
				else
				{
					$result[$warehouseId][] = $storeId;
				}
			}
		}
		else if ($options->useStoreGroup())
		{
			list($warehouseId, $campaignId) = Market\Api\Business\Warehouses\Facade::primaryWarehouse($options);

			$this->warehouseCampaignId = $campaignId;
			$result[$warehouseId] = $options->getProductStores();
		}
		else
		{
			$result[$options->getWarehousePrimary()] = $options->getProductStores();
		}

		return $result;
	}

	protected function applyAmountsSku($amounts, array $used = [])
	{
		$productIds = array_column($amounts, 'ID');
		$skuMap = $this->getSkuMap($productIds);

		if ($skuMap === null) { return $amounts; }

		$result = [];

		foreach ($amounts as $amount)
		{
			if (!isset($skuMap[$amount['ID']])) { continue; }

			$sku = trim($skuMap[$amount['ID']]);

			if (isset($used[$sku])) { continue; }

			$amount['~ID'] = $amount['ID'];
			$amount['ID'] = $sku;

			$result[] = $amount;
			$used[$sku] = true;
		}

		return $result;
	}

	protected function getSkuMap($productIds)
	{
		$command = new TradingService\Common\Command\SkuMap(
			$this->provider,
			$this->environment
		);

		return $command->make($productIds);
	}

	protected function applyMissing($amounts, $productIds)
	{
		foreach ($this->getWarehouseMap() as $warehouseId => $stores)
		{
			$warehouseAmounts = array_filter($amounts, static function($amount) use ($warehouseId) {
				return $amount['WAREHOUSE_ID'] === $warehouseId;
			});
			$warehouseExists = array_column($warehouseAmounts, 'ID', 'ID');
			$missingMap = array_diff_key(array_flip($productIds), $warehouseExists);

			foreach ($missingMap as $productId => $dummy)
			{
				$amounts[] = [
					'ID' => $productId,
					'WAREHOUSE_ID' => $warehouseId,
					'QUANTITY' => 0,
					'TIMESTAMP_X' => new Main\Type\DateTime(),
				];
			}
		}

		return $amounts;
	}

	protected function applyDeleted($amounts, $productIds)
	{
		foreach ($this->getWarehouseMap() as $warehouseId => $stores)
		{
			foreach ($productIds as $productId)
			{
				$amounts[] = [
					'ID' => $productId,
					'WAREHOUSE_ID' => $warehouseId,
					'QUANTITY' => 0,
					'TIMESTAMP_X' => new Main\Type\DateTime(),
				];
			}
		}

		return $amounts;
	}

	protected function applyEmulatedUpdated($amounts)
	{
		foreach ($amounts as &$amount)
		{
			$amount['TIMESTAMP_X'] = new Main\Type\DateTime();
		}
		unset($amount);

		return $amounts;
	}

	protected function applyReserves($amounts)
	{
		if (!$this->provider->getOptions()->useOrderReserve()) { return $amounts; }

		$command = new TradingService\Marketplace\Command\ProductReserves(
			$this->provider,
			$this->environment,
			$this->getPlatform()
		);

		return $command->execute($amounts);
	}

	protected function applyPackRatio($amounts)
	{
		$command = new TradingService\Marketplace\Command\AmountsPackRatio(
			$this->provider,
			$this->environment
		);

		return $command->execute($amounts);
	}

	protected function extendPushed($amounts, $productIds)
	{
		$existsIds = array_map(static function($amount) { return isset($amount['~ID']) ? $amount['~ID'] : $amount['ID']; }, $amounts);
		$missingMap = array_diff_key(array_flip($productIds), array_flip($existsIds));
		$missingIds = array_keys($missingMap);

		if (empty($missingIds)) { return $amounts; }

		$existsSkus = array_fill_keys(array_column($amounts, 'ID'), true);

		$missing = $this->applyDeleted([], $missingIds);
		$missing = $this->applyAmountsSku($missing, $existsSkus);
		$missing = $this->getPushStore()->filterExists($missing, [ '>VALUE' => 0 ]);

		if (empty($missing)) { return $amounts; }

		array_push($amounts, ...$missing);

		return $amounts;
	}

	protected function buildSkus($amounts)
	{
		$result = [];

		foreach ($amounts as $amount)
		{
			$updatedAt = Market\Data\Date::convertForService($amount['TIMESTAMP_X']);
			$item = [
				'sku' => (string)$amount['ID'],
				'warehouseId' => (string)$amount['WAREHOUSE_ID'],
				'items' => []
			];

			if (isset($amount['QUANTITY_LIST']))
			{
				foreach ($amount['QUANTITY_LIST'] as $type => $quantity)
				{
					$item['items'][] = [
						'type' => $type,
						'count' => (string)$this->normalizeItemCount($quantity),
						'updatedAt' => $updatedAt
					];
				}
			}
			else if (isset($amount['QUANTITY']))
			{
				$item['items'][] = [
					'type' => Market\Data\Trading\Stocks::TYPE_FIT,
					'count' => (string)$this->normalizeItemCount($amount['QUANTITY']),
					'updatedAt' => $updatedAt
				];
			}

			$result[] = $item;
		}

		return $result;
	}

	protected function filterMatchedWarehouse($amounts)
	{
		$warehouseMap = $this->getWarehouseMap();

		foreach ($amounts as $key => $amount)
		{
			if (!isset($warehouseMap[$amount['WAREHOUSE_ID']]))
			{
				unset($amounts[$key]);
			}
		}

		return $amounts;
	}

	protected function emulateSkus($amounts)
	{
		$result = [];
		$updatedAt = new Main\Type\DateTime();
		$updatedAt = Market\Data\Date::convertForService($updatedAt);

		foreach ($amounts as $amount)
		{
			$result[] = [
				'sku' => (string)$amount['ID'],
				'warehouseId' => (string)$amount['WAREHOUSE_ID'],
				'items' => [
					[
						'type' => Market\Data\Trading\Stocks::TYPE_FIT,
						'count' => '0',
						'updatedAt' => $updatedAt,
					],
				],
			];
		}

		return $result;
	}

	protected function normalizeItemCount($count)
	{
		return max(0, (int)$count);
	}

	protected function getSkusChunkSize()
	{
		return (int)Market\Config::getOption('push_stocks_chunk', 2000);
	}

	protected function sendSkus($skus)
	{
		if (empty($skus)) { return; }

		$request = new TradingService\Marketplace\Api\SendStocks\Request();
		$options = $this->provider->getOptions();
		$logger = $this->provider->getLogger();

		$request->setLogger($logger);
		$request->setOauthClientId($options->getOauthClientId());
		$request->setOauthToken($options->getOauthToken()->getAccessToken());
		$request->setCampaignId($this->warehouseCampaignId ?: $options->getCampaignId());
		$request->setSkus($skus);

		$result = $request->send();

		Market\Exceptions\Api\Facade::handleResult($result, self::getMessage('SEND_FAILED'));
	}

	protected function getPushStore()
	{
		if ($this->pushStore === null)
		{
			$this->pushStore = $this->creatPushStore();
		}

		return $this->pushStore;
	}

	protected function creatPushStore()
	{
		$setupId =
			$this->provider->getOptions()->getStoreGroupPrimarySetup()
			?: $this->provider->getOptions()->getSetupId();

		return new Market\Trading\State\PushStore(
			$setupId,
			Market\Trading\Entity\Registry::ENTITY_TYPE_STOCKS,
			['ID', 'WAREHOUSE_ID'],
			[$this, 'pushStoreSign']
		);
	}

	public function pushStoreSign($amount)
	{
		if (isset($amount['QUANTITY_LIST']))
		{
			$parts = [];

			foreach ($amount['QUANTITY_LIST'] as $type => $quantity)
			{
				$parts[] = $type . '=' . (int)$quantity;
			}

			$result = implode(':', $parts);
		}
		else if (isset($amount['QUANTITY']))
		{
			$result = (int)$amount['QUANTITY'];
		}
		else
		{
			$result = null;
		}

		return $result;
	}
}