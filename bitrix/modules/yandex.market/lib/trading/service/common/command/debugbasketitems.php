<?php

namespace Yandex\Market\Trading\Service\Common\Command;

use Bitrix\Main;
use Yandex\Market;
use Yandex\Market\Trading\Service as TradingService;
use Yandex\Market\Trading\Entity as TradingEntity;

class DebugBasketItems
{
	protected $provider;
	protected $environment;
	protected $platform;
	protected $order;
	protected $items;
	protected $basketMap;
	protected $productMap;
	protected $packRatio;

	protected $matchErrorCodes = [
		'COUNT_NOT_MATCH' => true,
		'OFFER_NOT_EXISTS' => true,
	];

	public function __construct(
		TradingService\Reference\Provider $provider,
		TradingEntity\Reference\Environment $environment,
		TradingEntity\Reference\Platform $platform,
		TradingEntity\Reference\Order $order,
		Market\Api\Model\Cart\ItemCollection $items,
		array $basketMap,
		array $productMap,
		array $packRatio
	)
	{
		$this->provider = $provider;
		$this->environment = $environment;
		$this->platform = $platform;
		$this->order = $order;
		$this->items = $items;
		$this->basketMap = $basketMap;
		$this->productMap = $productMap;
		$this->packRatio = $packRatio;
	}

	public function need(Market\Result\Base $validation)
	{
		if ($validation->isSuccess()) { return false; }

		$result = false;

		foreach ($validation->getErrors() as $error)
		{
			if ($this->matchError($error))
			{
				$result = true;
				break;
			}
		}

		return $result;
	}

	/**
	 * @param Market\Error\Base|Main\Error $error
	 *
	 * @return bool
	 */
	protected function matchError($error)
	{
		$code = $error->getCode();

		return isset($this->matchErrorCodes[$code]);
	}

	public function execute()
	{
		$productIds = array_values(array_unique($this->productMap));
		$productData = $this->productData($productIds);
		$storeData = $this->storeData($productIds);
		$pushData = $this->pushData($productIds);
		$reserveData = $this->reserveData($productIds);
		$result = [];

		/** @var Market\Api\Model\Cart\Item $item */
		foreach ($this->items as $itemIndex => $item)
		{
			$productId = isset($this->productMap[$itemIndex]) ? $this->productMap[$itemIndex] : null;
			$ratio = isset($this->packRatio[$itemIndex]) ? $this->packRatio[$itemIndex] : 1;
			$partials = [];

			if (isset($this->basketMap[$itemIndex]))
			{
				$basketCode = $this->basketMap[$itemIndex];

				$partials['BASKET'] = $this->basketData($basketCode, $item, $productId, $ratio);
			}

			if ($productId !== null)
			{
				$partials['PRODUCT'] = isset($productData[$productId]) ? $productData[$productId] : null;
				$partials['STORE'] = isset($storeData[$productId]) ? $storeData[$productId] : null;
				$partials['PUSH'] = isset($pushData[$productId]) ? $pushData[$productId] : null;
				$partials['RESERVE'] = isset($reserveData[$productId]) ? $reserveData[$productId] : null;
			}

			$data = [
				'SKU' => $item->getOfferId(),
				'ID' => $productId,
				'COUNT' => $item->getCount(),
			];

			if ((float)$ratio !== 1.0)
			{
				$data['RATIO'] = $ratio;
			}

			$data += $this->combinePartials($partials);

			$result[] = $data;
		}

		return $result;
	}

	protected function basketData($basketCode, Market\Api\Model\Cart\Item $item, $productId, $ratio = 1)
	{
		return $this->order->debugBasketItem($basketCode, [
			'PRODUCT_ID' => $productId,
			'QUANTITY' => $item->getCount() * $ratio,
		]);
	}

	protected function productData($productIds)
	{
		return $this->environment->getProduct()->debugBasketData($productIds);
	}

	protected function storeData($productIds)
	{
		$options = $this->provider->getOptions();

		if (
			$options instanceof TradingService\Marketplace\Options
			&& $options->useWarehouses()
		)
		{
			return [];
		}

		if (
			!$options->isProductStoresTrace()
			&& in_array(TradingEntity\Common\Store::PRODUCT_FIELD_QUANTITY, $options->getProductStores(), true)
		)
		{
			return [];
		}

		$stores = $options->getProductStores();
		$amounts = $this->environment->getStore()->getAmounts($stores, $productIds);
		$quantityMap = $this->mapAmountsQuantity($amounts);
		$result = [];

		foreach ($productIds as $productId)
		{
			$result[$productId] = [
				'QUANTITY' => isset($quantityMap[$productId]) ? $quantityMap[$productId] : null,
			];
		}

		return $result;
	}

	protected function mapAmountsQuantity(array $amounts)
	{
		$result = [];

		foreach ($amounts as $amount)
		{
			$productId = $amount['ID'];
			$count = 0;

			if (isset($amount['QUANTITY_LIST']))
			{
				$count = array_sum($amounts['QUANTITY_LIST']);
			}
			else if (isset($amount['QUANTITY']))
			{
				$count = $amount['QUANTITY'];
			}

			if (!isset($result[$productId]))
			{
				$result[$productId] = $count;
			}
			else
			{
				$result[$productId] += $count;
			}
		}

		return $result;
	}

	protected function pushData(array $productIds)
	{
		$options = $this->provider->getOptions();

		if (
			!($options instanceof TradingService\Marketplace\Options)
			|| $options->useWarehouses()
			|| !$options->usePushStocks()
		)
		{
			return [];
		}

		if (empty($productIds)) { return []; }

		$storeId = $options->getWarehousePrimary();
		$result = [];

		$productMap = array_combine(
			array_map(function($productId) use ($storeId) { return $productId . ':' . $storeId; }, $productIds),
			$productIds
		);

		$query = Market\Trading\State\Internals\PushTable::getList([
			'filter' => [
				'=SETUP_ID' => $options->getSetupId(),
				'=ENTITY_TYPE' => Market\Trading\Entity\Registry::ENTITY_TYPE_STOCKS,
				'=ENTITY_ID' => array_keys($productMap),
			],
			'select' => [
				'ENTITY_ID',
				'VALUE',
				'TIMESTAMP_X',
			],
		]);

		while ($row = $query->fetch())
		{
			$productId = $productMap[$row['ENTITY_ID']];

			$result[$productId] = [
				'QUANTITY' => $row['VALUE'],
				'TIMESTAMP_X' => (string)$row['TIMESTAMP_X'],
			];
		}

		return $result;
	}

	protected function reserveData(array $productIds)
	{
		if (!($this->provider instanceof TradingService\Marketplace\Provider)) { return []; }

		$behavior = $this->provider->getOptions()->getStocksBehavior();

		if ($behavior === TradingService\Marketplace\Options::STOCKS_WITH_RESERVE)
		{
			$result = $this->basketReserves($productIds);

			foreach ($this->stocksReserves($productIds) as $productId => $stocksReserve)
			{
				$result[$productId]['STOCKS'] = $stocksReserve;
			}
		}
		else if ($behavior === TradingService\Marketplace\Options::STOCKS_ONLY_AVAILABLE)
		{
			$result = $this->basketReserves($productIds);
		}
		else
		{
			$result = [];
		}

		return $result;
	}

	protected function basketReserves(array $productIds)
	{
		if (!($this->provider instanceof TradingService\Marketplace\Provider)) { return []; }

		$command = new TradingService\Marketplace\Command\BasketReserves($this->provider, $this->environment, $this->platform);
		$emulatedQuantity = 10000;
		$emulatedStoreData = array_fill_keys($productIds, [ 'AVAILABLE_QUANTITY' => $emulatedQuantity ]);
		$result = [];

		foreach ($command->execute($emulatedStoreData) as $productId => $productData)
		{
			$row = [
				'QUANTITY' => 0,
			];

			foreach ($command->findDebugProduct($productId) as $key => $reserve)
			{
				$row['QUANTITY'] += $reserve['QUANTITY'];
				$row[$key] = isset($reserve['ORDER']) ? $reserve['ORDER'] : $reserve['QUANTITY'];
			}

			if ((float)$row['QUANTITY'] === 0.0) { continue; }

			$result[$productId] = $row;
		}

		return $result;
	}

	protected function stocksReserves(array $productIds)
	{
		if (!($this->provider instanceof TradingService\Marketplace\Provider)) { return []; }

		$command = new TradingService\Marketplace\Command\ProductReserves($this->provider, $this->environment, $this->platform);
		$emulatedDate = new Main\Type\DateTime();
		$emulatedDate->add('-P30D');
		$emulatedQuantity = 10000;
		$emulatedAmounts = array_map(static function($productId) use ($emulatedQuantity, $emulatedDate) {
			return [
				'ID' => $productId,
				'TIMESTAMP_X' => $emulatedDate,
				'QUANTITY' => $emulatedQuantity,
			];
		}, $productIds);

		$result = [];

		foreach ($command->execute($emulatedAmounts) as $amount)
		{
			$diff = $amount['QUANTITY'] - $emulatedQuantity;

			if ($diff === 0) { continue; }

			$result[$amount['ID']] = $diff;
		}

		return $result;
	}

	protected function combinePartials(array $partials)
	{
		$result = [];

		foreach ($partials as $group => $values)
		{
			if (empty($values)) { continue; }

			foreach ($values as $name => $value)
			{
				$result[$group . '_' . $name] = $value;
			}
		}

		return $result;
	}
}