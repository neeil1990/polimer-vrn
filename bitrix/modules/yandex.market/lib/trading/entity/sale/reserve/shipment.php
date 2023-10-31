<?php

namespace Yandex\Market\Trading\Entity\Sale\Reserve;

use Yandex\Market;
use Bitrix\Main;
use Bitrix\Sale;

class Shipment extends Skeleton
{
	public function getWaiting(array $orderIds, array $productIds)
	{
		if ($this->isReservedOnCreate()) { return []; }

		$waitingOrderIds = $this->findWaitingOrders($orderIds);
		$basket = $this->mapBasketProducts($waitingOrderIds, $productIds);

		return $this->combineBasketQuantities($basket);
	}

	public function getReserved(array $orderIds, array $productIds)
	{
		if ($this->usedAvailableQuantity || $this->isReservedEqualShipped()) { return []; }

		$orders = $this->findReservedOrders($orderIds);

		$basket = $this->mapBasketProducts(
			array_unique(array_column($orders, 'ORDER_ID')),
			$productIds
		);

		return $this->loadShipmentReserves($orders, $basket);
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
			$this->loadShipmentReserves($reservedOrders, $reserveBasket),
			$this->loadShipmentShipped($shippedOrders, $shippedBasket)
		);
	}

	public function getSiblingReserved(array $orderIds, array $productIds, Main\Type\DateTime $after)
	{
		if (
			$this->usedAvailableQuantity
			|| !$this->allowedSiblingReserve()
			|| $this->isReservedEqualShipped()
		)
		{
			return [];
		}

		$reservedOrders = $this->findSiblingReserved($productIds, $after, $orderIds);

		$reserveBasket = $this->mapBasketProducts(
			array_unique(array_column($reservedOrders, 'ORDER_ID')),
			$productIds
		);

		return $this->loadShipmentReserves($reservedOrders, $reserveBasket, true);
	}

	protected function groupOrders(array $orderIds, Main\Type\DateTime $after = null)
	{
		$shipped = $this->findShippedOrders($orderIds, $after);

		if (!$this->usedAvailableQuantity || $this->isReservedEqualShipped())
		{
			$reserved = [];
		}
		else
		{
			$found = array_unique(array_column($shipped, 'ORDER_ID'));
			$left = array_diff($orderIds, $found);

			$reserved = $this->findReservedOrders($left, $after, true);
		}

		return [$reserved, $shipped];
	}

	protected function findWaitingOrders(array $orderIds)
	{
		$shipped = $this->findShippedOrders($orderIds);
		$shippedOrderIds = array_column($shipped, 'ORDER_ID');
		$reserved = $this->findReservedOrders(array_diff($orderIds, $shippedOrderIds), null, true);
		$reservedOrderIds = array_column($reserved, 'ORDER_ID');

		return array_diff($orderIds, $shippedOrderIds, $reservedOrderIds);
	}

	/**
	 * @param int[] $orderIds
	 * @param Main\Type\DateTime|null $after
	 * @param boolean $skipCheckDeducted
	 *
	 * @return array{ORDER_ID: int, DATE: Main\Type\DateTime|null}
	 */
	protected function findReservedOrders(array $orderIds, Main\Type\DateTime $after = null, $skipCheckDeducted = false)
	{
		/** @var Main\Entity\DataManager $dataClass */
		$rule = $this->reserveRule();
		$orderField = $rule['ENTITY'] === 'ORDER' ? 'ID' : 'ORDER_ID';
		$dataClass = $this->entityDataClass($rule['ENTITY']);
		$result = [];

		foreach (array_chunk($orderIds, 500) as $orderChunk)
		{
			$filter = [
				'=' . $orderField => $orderChunk,
			];

			if ($rule['FLAG'] !== null)
			{
				$filter['=' . $rule['FLAG']] = 'Y';
			}

			if (!$skipCheckDeducted)
			{
				$filter['!=' . $this->entityOrderReference($rule['ENTITY'], 'DEDUCTED')] = 'Y';
			}

			if ($after !== null)
			{
				$filter['>=' . $rule['DATE']] = $this->adjustAfterFilter($after);
			}

			$query = $dataClass::getList([
				'filter' => $filter,
				'select' => [
					$orderField,
					$rule['DATE'],
				],
			]);

			while ($row = $query->fetch())
			{
				$orderId = $row[$orderField];
				$date = $row[$rule['DATE']];

				if (!isset($result[$orderId]))
				{
					$result[$orderId] = [
						'ORDER_ID' => $row[$orderField],
						'DATE' => $date,
					];
				}
				else if (
					$result[$orderId]['DATE'] === null
					|| (
						$date !== null
						&& Market\Data\DateTime::compare($date, $result[$orderId]['DATE']) === 1
					)
				)
				{
					$result[$orderId]['DATE'] = $date;
				}
			}
		}

		return array_values($result);
	}

	protected function findSiblingReserved(array $productIds, Main\Type\DateTime $after, array $skipOrderIds)
	{
		/** @var Main\Entity\DataManager $dataClass */
		$rule = $this->reserveRule();
		$orderField = $rule['ENTITY'] === 'ORDER' ? 'ID' : 'ORDER_ID';
		$dataClass = $this->entityDataClass($rule['ENTITY']);
		$result = [];

		foreach (array_chunk($productIds, 500) as $productChunk)
		{
			$filter = [];

			if (!empty($skipOrderIds))
			{
				$filter['!=' . $orderField] = $skipOrderIds;
			}

			$filter['>=' . $rule['DATE']] = $this->adjustAfterFilter($after);

			if ($rule['FLAG'] !== null)
			{
				$filter['=' . $rule['FLAG']] = 'Y';
			}

			$filter['!=' . $this->entityOrderReference($rule['ENTITY'], 'DEDUCTED')] = 'Y';
			$filter['=' . $this->entityBasketItemReference($rule['ENTITY'], 'PRODUCT_ID')] = $productChunk;

			$query = $dataClass::getList([
				'filter' => $filter,
				'select' => [
					$orderField,
				],
			]);

			while ($row = $query->fetch())
			{
				$orderId = $row[$orderField];

				$result[$orderId] = [
					'ORDER_ID' => $orderId,
				];
			}
		}

		return array_values($result);
	}

	protected function loadShipmentReserves(array $reservedOrders, array $basketProducts, $forSibling = false)
	{
		if (!$this->usedAvailableQuantity && $this->usedStoreControl())
		{
			$needCheckDefault = (!$forSibling || $this->isDefaultStoreUsed());
			list($result, $usedBasketIds) = $this->loadShipmentReservesFromStoreItem($reservedOrders, $basketProducts, $needCheckDefault);

			if ($needCheckDefault)
			{
				$leftBasketProducts = array_diff_key($basketProducts, array_flip($usedBasketIds));

				$result = $this->mergeAmounts(
					$result,
					$this->loadShipmentReservesFromItem($reservedOrders, $leftBasketProducts)
				);
			}
		}
		else
		{
			$result = $this->loadShipmentReservesFromItem($reservedOrders, $basketProducts);
		}

		return $result;
	}

	protected function loadShipmentReservesFromStoreItem(array $reservedOrders, array $basketProducts, $needCheckDefault = false)
	{
		if (empty($this->usedStores)) { return [ [], [] ]; }

		$clearReservePeriod = $this->clearReservePeriod();
		$reservedMap = array_column($reservedOrders, 'DATE', 'ORDER_ID');
		$result = [];
		$usedBasketIds = [];
		$usedBasketMap = array_flip($this->usedStores);

		foreach (array_chunk($basketProducts, 500, true) as $basketChunk)
		{
			$filter = [
				'=BASKET_ID' => array_keys($basketChunk),
			];

			if (!$needCheckDefault)
			{
				$filter['=STORE_ID'] = $this->usedStores;
			}

			$query = Sale\Internals\ShipmentItemStoreTable::getList([
				'filter' => $filter,
				'select' => [
					'DATE_CREATE',
					'BASKET_ID',
					'STORE_ID',
					'QUANTITY',
					'RESERVED_QUANTITY' => 'YA_SHIPMENT_ITEM.RESERVED_QUANTITY',
				],
				'runtime' => [
					new Main\Entity\ReferenceField(
						'YA_SHIPMENT_ITEM',
						Sale\Internals\ShipmentItemTable::class,
						[ '=this.ORDER_DELIVERY_BASKET_ID' => 'ref.ID' ]
					),
				],
			]);

			while ($row = $query->fetch())
			{
				$usedBasketIds[] = $row['BASKET_ID'];

				if (!isset($usedBasketMap[$row['STORE_ID']])) { continue; }

				$basketRow = $basketChunk[$row['BASKET_ID']];
				$productId = $basketRow['PRODUCT_ID'];
				$quantity = min((float)$row['QUANTITY'], (float)$row['RESERVED_QUANTITY']);

				if ($quantity > 0)
				{
					$orderId = $basketRow['ORDER_ID'];
					$timestamp = $reservedMap[$orderId] ?: $row['DATE_CREATE'];
				}
				else
				{
					$quantity = 0;
					$timestamp = Market\Data\DateTime::min(
						$row['DATE_CREATE']->add(sprintf('P%sD', $clearReservePeriod)),
						new Main\Type\DateTime()
					);
				}

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

		return [ $result, $usedBasketIds ];
	}

	protected function loadShipmentReservesFromItem(array $reservedOrders, array $basketProducts)
	{
		$clearReservePeriod = $this->clearReservePeriod();
		$canClearReserve = ($clearReservePeriod > 0);
		$reservedMap = array_column($reservedOrders, 'DATE', 'ORDER_ID');
		$result = [];

		foreach (array_chunk($basketProducts, 500, true) as $basketChunk)
		{
			$filter = [
				'=BASKET_ID' => array_keys($basketChunk),
			];

			if (!$canClearReserve)
			{
				$filter['>RESERVED_QUANTITY'] = 0;
			}

			$query = Sale\Internals\ShipmentItemTable::getList([
				'filter' => $filter,
				'select' => [
					'DATE_INSERT',
					'BASKET_ID',
					'RESERVED_QUANTITY',
				],
			]);

			while ($row = $query->fetch())
			{
				$basketRow = $basketChunk[$row['BASKET_ID']];
				$productId = $basketRow['PRODUCT_ID'];
				$quantity = (float)$row['RESERVED_QUANTITY'];

				if ($quantity > 0)
				{
					$orderId = $basketRow['ORDER_ID'];
					$timestamp = $reservedMap[$orderId] ?: $row['DATE_INSERT'];
				}
				else
				{
					$quantity = 0;
					$timestamp = Market\Data\DateTime::min(
						$row['DATE_INSERT']->add(sprintf('P%sD', $clearReservePeriod)),
						new Main\Type\DateTime()
					);
				}

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

	protected function reserveRule()
	{
		$condition = Sale\Configuration::getProductReservationCondition();

		if ($condition === $this->saleReverseConstant('ON_ALLOW_DELIVERY'))
		{
			$entity = 'SHIPMENT';
			$flag = 'ALLOW_DELIVERY';
			$date = 'DATE_ALLOW_DELIVERY';
		}
		else if ($condition === $this->saleReverseConstant('ON_SHIP'))
		{
			$entity = 'SHIPMENT';
			$flag = 'DEDUCTED';
			$date = 'DATE_DEDUCTED';
		}
		else if ($condition === $this->saleReverseConstant('ON_PAY'))
		{
			$entity = 'PAYMENT';
			$flag = 'PAID';
			$date = 'DATE_PAID';
		}
		else if ($condition === $this->saleReverseConstant('ON_FULL_PAY'))
		{
			$entity = 'ORDER';
			$flag = 'PAYED';
			$date = 'DATE_PAYED';
		}
		else
		{
			$entity = 'ORDER';
			$flag = null;
			$date = 'DATE_INSERT';
		}

		return [
			'ENTITY' => $entity,
			'FLAG' => $flag,
			'DATE' => $date,
		];
	}

	protected function clearReservePeriod()
	{
		return Sale\Configuration::getProductReserveClearPeriod();
	}

	/**
	 * @param string $entity
	 *
	 * @return class-string<Main\Entity\DataManager>
	 */
	protected function entityDataClass($entity)
	{
		if ($entity === 'ORDER')
		{
			$result = Sale\Internals\OrderTable::class;
		}
		else if ($entity === 'PAYMENT')
		{
			$result = Sale\Internals\PaymentTable::class;
		}
		else if ($entity === 'SHIPMENT')
		{
			$result = Sale\Internals\ShipmentTable::class;
		}
		else
		{
			throw new Main\SystemException(sprintf('cant map entity %s to data class', $entity));
		}

		return $result;
	}

	protected function entityBasketItemReference($entity, $field)
	{
		return $this->entityOrderReference($entity, 'BASKET.' . $field);
	}

	protected function entityOrderReference($entity, $field)
	{
		if ($entity === 'ORDER')
		{
			$result = $field;
		}
		else if ($entity === 'PAYMENT')
		{
			$result = 'ORDER.' . $field;
		}
		else if ($entity === 'SHIPMENT')
		{
			$result = 'ORDER.' . $field;
		}
		else
		{
			throw new Main\SystemException(sprintf('cant map entity %s to data class', $entity));
		}

		return $result;
	}
}