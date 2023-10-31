<?php

namespace Yandex\Market\Trading\Entity\Sale\Reserve;

use Bitrix\Main;
use Bitrix\Sale;
use Bitrix\Catalog;
use Yandex\Market;
use Yandex\Market\Trading\Entity\Reference as EntityReference;
use Yandex\Market\Trading\Entity\Common as EntityCommon;

abstract class Skeleton extends EntityReference\Reserve
{
	protected $usedAvailableQuantity = true;
	protected $usedStores;
	protected $defaultStoreUsed;

	public function configure(array $context)
	{
		$this->usedAvailableQuantity = in_array(EntityCommon\Store::PRODUCT_FIELD_QUANTITY, $context['STORES'], true);
		$this->usedStores = $context['STORES'];
		$this->defaultStoreUsed = null;
	}

	public function mapProducts(array $orderIds, array $productIds, Main\Type\DateTime $after = null)
	{
		list($reservedOrders, $shippedOrders) = $this->groupOrders($orderIds, $after);

		$foundOrders = array_merge(
			array_unique(array_column($reservedOrders, 'ORDER_ID')),
			array_unique(array_column($shippedOrders, 'ORDER_ID'))
		);
		$result = [];

		foreach ($this->mapBasketProducts($foundOrders, $productIds) as $basketProduct)
		{
			$orderId = (int)$basketProduct['ORDER_ID'];
			$productId = (string)$basketProduct['PRODUCT_ID'];

			if (!isset($result[$orderId]))
			{
				$result[$orderId] = [];
			}

			$result[$orderId][] = $productId;
		}

		return $result;
	}

	protected function usedStoreControl()
	{
		return Main\Config\Option::get('catalog', 'default_use_store_control') === 'Y';
	}

	protected function allowedSiblingReserve()
	{
		return (Market\Config::getOption('experiment_trading_sibling_reserved_stop', 'N') !== 'Y');
	}

	abstract protected function groupOrders(array $orderIds, Main\Type\DateTime $after = null);

	protected function findShippedOrders(array $orderIds, Main\Type\DateTime $after = null)
	{
		$result = [];

		foreach (array_chunk($orderIds, 500) as $orderChunk)
		{
			$filter = [
				'=ORDER_ID' => $orderChunk,
				'=DEDUCTED' => 'Y',
			];

			if ($after !== null)
			{
				$filter['>=DATE_DEDUCTED'] = $this->adjustAfterFilter($after);
			}

			$query = Sale\Internals\ShipmentTable::getList([
				'filter' => $filter,
				'select' => [
					'ID',
					'ORDER_ID',
					'DATE_DEDUCTED',
				],
			]);

			while ($row = $query->fetch())
			{
				$result[] = [
					'SHIPMENT_ID' => $row['ID'],
					'ORDER_ID' => $row['ORDER_ID'],
					'DATE' => $row['DATE_DEDUCTED'],
				];
			}
		}

		return $result;
	}

	protected function adjustAfterFilter(Main\Type\DateTime $after)
	{
		$result = clone $after;
		$result->add('-PT10S'); // allowed gap between product and order change

		return $result;
	}

	protected function loadShipmentShipped(array $shippedOrders, array $basketProducts)
	{
		$result = [];
		$shippedMap = array_column($shippedOrders, 'DATE', 'SHIPMENT_ID');

		foreach (array_chunk($basketProducts, 500, true) as $basketChunk)
		{
			$query = Sale\Internals\ShipmentItemTable::getList([
				'filter' => [
					'=BASKET_ID' => array_keys($basketChunk),
				],
				'select' => [
					'DATE_INSERT',
					'BASKET_ID',
					'ORDER_DELIVERY_ID',
					'QUANTITY',
				],
			]);

			while ($row = $query->fetch())
			{
				$shipmentId = $row['ORDER_DELIVERY_ID'];

				if (!isset($shippedMap[$shipmentId]) && !array_key_exists($shipmentId, $shippedMap)) { continue; } // not shipped

				$basketRow = $basketChunk[$row['BASKET_ID']];
				$productId = $basketRow['PRODUCT_ID'];
				$quantity = (float)$row['QUANTITY'];
				$timestamp = $shippedMap[$shipmentId] ?: $row['DATE_INSERT'];

				if (!isset($result[$productId]))
				{
					$result[$productId] = [
						'QUANTITY' => $quantity,
						'TIMESTAMP_X' => $timestamp
					];
				}
				else
				{
					$result[$productId]['QUANTITY'] += $quantity;
					$result[$productId]['TIMESTAMP_X'] = Market\Data\DateTime::max(
						$result[$productId]['TIMESTAMP_X'],
						$timestamp
					);
				}
			}
		}

		return $result;
	}

	protected function mergeAmounts(array ...$groups)
	{
		$result = array_shift($groups);

		if ($result === null) { return []; }

		foreach ($groups as $group)
		{
			foreach ($group as $productId => $amount)
			{
				if (!isset($result[$productId]))
				{
					$result[$productId] = $amount;
				}
				else
				{
					$result[$productId]['QUANTITY'] += $amount['QUANTITY'];
					$result[$productId]['TIMESTAMP_X'] = Market\Data\DateTime::max(
						$result[$productId]['TIMESTAMP_X'],
						$amount['TIMESTAMP_X']
					);
				}
			}
		}

		return $result;
	}

	protected function mapBasketProducts(array $orderIds, array $productIds)
	{
		$result = [];

		foreach (array_chunk($orderIds, 500) as $orderChunk)
		{
			foreach (array_chunk($productIds, 500) as $productChunk)
			{
				$query = Sale\Internals\BasketTable::getList([
					'filter' => [
						'=ORDER_ID' => $orderChunk,
						'=PRODUCT_ID' => $productChunk,
					],
					'select' => [
						'ID',
						'PRODUCT_ID',
						'ORDER_ID',
						'QUANTITY',
					],
				]);

				while ($row = $query->fetch())
				{
					$result[$row['ID']] = [
						'PRODUCT_ID' => $row['PRODUCT_ID'],
						'ORDER_ID' => $row['ORDER_ID'],
						'QUANTITY' => (float)$row['QUANTITY'],
					];
				}
			}
		}

		return $result;
	}

	protected function combineBasketQuantities(array $basket)
	{
		$result = [];

		foreach ($basket as $row)
		{
			$productId = $row['PRODUCT_ID'];

			if (!isset($result[$productId]))
			{
				$result[$productId] = [
					'QUANTITY' => 0,
					'ORDER' => [],
				];
			}

			$result[$productId]['QUANTITY'] += $row['QUANTITY'];
			$result[$productId]['ORDER'][$row['ORDER_ID']] = isset($result[$productId]['ORDER'][$row['ORDER_ID']])
				? $result[$productId]['ORDER'][$row['ORDER_ID']] + $row['QUANTITY']
				: $row['QUANTITY'];
		}

		return $result;
	}

	protected function isDefaultStoreUsed()
	{
		if (empty($this->usedStores)) { return false; }
		if ($this->defaultStoreUsed !== null) { return $this->defaultStoreUsed; }

		if (method_exists(Catalog\StoreTable::class, 'getDefaultStoreId'))
		{
			$defaultStoreId = Catalog\StoreTable::getDefaultStoreId();
			$usedMap = array_flip($this->usedStores);

			$this->defaultStoreUsed = isset($usedMap[$defaultStoreId]);
		}
		else if (Catalog\StoreTable::getEntity()->hasField('IS_DEFAULT'))
		{
			$query = Catalog\StoreTable::getList([
				'select' => [ 'ID' ],
				'filter' => [
					'=ID' => $this->usedStores,
					'=IS_DEFAULT' => 'Y',
				],
				'limit' => 1,
			]);

			$this->defaultStoreUsed = (bool)$query->fetch();
		}
		else
		{
			$this->defaultStoreUsed = true;
		}

		return $this->defaultStoreUsed;
	}

	protected function isReservedOnCreate()
	{
		return (Sale\Configuration::getProductReservationCondition() === $this->saleReverseConstant('ON_CREATE'));
	}

	protected function isReservedEqualShipped()
	{
		return (Sale\Configuration::getProductReservationCondition() === $this->saleReverseConstant('ON_SHIP'));
	}

	protected function saleReverseConstant($name)
	{
		$newClassName = Sale\Reservation\Configuration\ReserveCondition::class;
		$oldClassName = Sale\Configuration::class;

		if (defined($newClassName . '::' . $name))
		{
			$result = constant($newClassName . '::' . $name);
		}
		else
		{
			$result = constant($oldClassName . '::RESERVE_' . $name);
		}

		return $result;
	}
}