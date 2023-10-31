<?php

namespace Yandex\Market\Trading\Entity\Sale\Reserve;

use Bitrix\Main;
use Bitrix\Sale;
use Yandex\Market;

class Basket extends Skeleton
{
	public function getWaiting(array $orderIds, array $productIds)
	{
		if ($this->isReservedOnCreate()) { return []; }

		$orderIds = $this->sliceShippedOrders($orderIds);

		$basket = $this->mapBasketProducts($orderIds, $productIds);
		$basket = $this->filterBasketWaiting($basket);

		return $this->combineBasketQuantities($basket);
	}

	public function getReserved(array $orderIds, array $productIds)
	{
		if ($this->usedAvailableQuantity) { return []; }

		$basket = $this->mapBasketProducts($orderIds, $productIds);

		return $this->loadBasketReserves($basket);
	}

	public function getAmounts(array $orderIds, array $productIds)
	{
		list($reservedOrders, $shippedOrders) = $this->groupOrders($orderIds);

		$reserveBasket = $this->mapBasketProducts(
			array_unique(array_column($reservedOrders, 'ORDER_ID')),
			$productIds
		);
		$shippedBasket = $this->mapBasketProducts(
			array_unique(array_column($shippedOrders, 'ORDER_ID')),
			$productIds
		);

		return $this->mergeAmounts(
			$this->loadBasketReserves($reserveBasket),
			$this->loadShipmentShipped($shippedOrders, $shippedBasket)
		);
	}

	public function getSiblingReserved(array $orderIds, array $productIds, Main\Type\DateTime $after)
	{
		if ($this->usedAvailableQuantity || !$this->allowedSiblingReserve()) { return []; }

		return $this->loadSiblingReserves($productIds, $after, $orderIds);
	}

	protected function sliceShippedOrders(array $orderIds)
	{
		$shipped = $this->findShippedOrders($orderIds);

		return array_diff(
			$orderIds,
			array_column($shipped, 'ORDER_ID')
		);
	}

	protected function groupOrders(array $orderIds, Main\Type\DateTime $after = null)
	{
		$shipped = $this->findShippedOrders($orderIds, $after);

		if (!$this->usedAvailableQuantity)
		{
			$reserved = [];
		}
		else
		{
			$found = array_unique(array_column($shipped, 'ORDER_ID'));
			$left = array_diff($orderIds, $found);

			$reserved = array_map(static function($orderId) {
				return [ 'ORDER_ID' => $orderId ];
			}, $left);
		}

		return [$reserved, $shipped];
	}

	protected function filterBasketWaiting(array $basketProducts)
	{
		/** @var Sale\ReserveQuantityCollection $reserveCollectionClassName */
		$registry = Sale\Registry::getInstance(Sale\Registry::ENTITY_ORDER);
		$reserveCollectionClassName = $registry->getReserveCollectionClassName();
		$found = [];

		foreach (array_chunk($basketProducts, 500, true) as $basketChunk)
		{
			$query = $reserveCollectionClassName::getList([
				'filter' => [ '=BASKET_ID' => array_keys($basketChunk), '>QUANTITY' => 0 ],
				'select' => [ 'BASKET_ID' ],
			]);

			while ($row = $query->fetch())
			{
				$found[$row['BASKET_ID']] = true;
			}
		}

		return array_diff_key($basketProducts, $found);
	}

	protected function loadBasketReserves(array $basketProducts)
	{
		/** @var Sale\ReserveQuantityCollection $reserveCollectionClassName */
		$registry = Sale\Registry::getInstance(Sale\Registry::ENTITY_ORDER);
		$reserveCollectionClassName = $registry->getReserveCollectionClassName();
		$result = [];

		foreach (array_chunk($basketProducts, 500, true) as $basketChunk)
		{
			$filter = [
				'=BASKET_ID' => array_keys($basketChunk),
			];

			if (!$this->usedAvailableQuantity && $this->usedStoreControl())
			{
				$filter['=STORE_ID'] = array_merge([0], $this->usedStores); // unallocated and target stores
			}

			$query = $reserveCollectionClassName::getList([
				'filter' => $filter,
				'select' => [
					'BASKET_ID',
					'QUANTITY',
					'DATE_RESERVE',
					'DATE_RESERVE_END',
				],
			]);

			while ($row = $query->fetch())
			{
				$basketRow = $basketChunk[$row['BASKET_ID']];
				$row['ORDER_ID'] = $basketRow['ORDER_ID'];

				$result = $this->pushResultReserve($result, $basketRow['PRODUCT_ID'], $row);
			}
		}

		return $result;
	}

	protected function loadSiblingReserves(array $productIds, Main\Type\DateTime $after = null, array $skipOrderIds = [])
	{
		/** @var Sale\ReserveQuantityCollection $reserveCollectionClassName */
		$registry = Sale\Registry::getInstance(Sale\Registry::ENTITY_ORDER);
		$reserveCollectionClassName = $registry->getReserveCollectionClassName();
		$result = [];

		foreach (array_chunk($productIds, 500) as $productChunk)
		{
			$filter = [
				'=YM_BASKET.PRODUCT_ID' => $productChunk,
			];

			if (!empty($skipOrderIds))
			{
				$filter['!=YM_BASKET.ORDER_ID'] = $skipOrderIds;
			}

			$filter['>QUANTITY'] = 0;

			if (!$this->usedAvailableQuantity && $this->usedStoreControl())
			{
				$filter['=STORE_ID'] = $this->isDefaultStoreUsed() ? array_merge([0], $this->usedStores) : $this->usedStores;
			}

			if ($after !== null)
			{
				$filter['>=DATE_RESERVE'] = $after;
			}

			$query = $reserveCollectionClassName::getList([
				'filter' => $filter,
				'select' => [
					'PRODUCT_ID' => 'YM_BASKET.PRODUCT_ID',
					'ORDER_ID' => 'YM_BASKET.ORDER_ID',
					'QUANTITY',
					'DATE_RESERVE',
					'DATE_RESERVE_END',
				],
				'runtime' => [
					new Main\Entity\ReferenceField('YM_BASKET', Sale\Internals\BasketTable::class, [
						'=this.BASKET_ID' => 'ref.ID',
					]),
				],
			]);

			while ($row = $query->fetch())
			{
				$result = $this->pushResultReserve($result, $row['PRODUCT_ID'], $row);
			}
		}

		return $result;
	}

	protected function pushResultReserve(array $result, $productId, array $row)
	{
		$quantity = (float)$row['QUANTITY'];

		if ($quantity > 0)
		{
			$timestamp = $row['DATE_RESERVE'];
		}
		else
		{
			$quantity = 0;
			$timestamp = $row['DATE_RESERVE_END'];
		}

		if (!isset($result[$productId]))
		{
			$result[$productId] = [
				'QUANTITY' => $quantity,
				'TIMESTAMP_X' => $timestamp,
				'ORDER' => [
					$row['ORDER_ID'] => $quantity,
				],
			];
		}
		else
		{
			$result[$productId]['QUANTITY'] += $quantity;
			$result[$productId]['TIMESTAMP_X'] = Market\Data\DateTime::max(
				$result[$productId]['TIMESTAMP_X'],
				$timestamp
			);
			$result[$productId]['ORDER'][$row['ORDER_ID']] = isset($result[$productId]['ORDER'][$row['ORDER_ID']])
				? $result[$productId]['ORDER'][$row['ORDER_ID']] + $quantity
				: $quantity;
		}

		return $result;
	}
}