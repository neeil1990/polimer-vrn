<?php

namespace Yandex\Market\Trading\Entity\Common\Digital;

use Bitrix\Main;
use Yandex\Market\Reference\Assert;
use Yandex\Market\Reference\Concerns;
use Yandex\Market\Trading\Entity\Reference as TradingReference;
use Yandex\Market\Trading\Entity\Sale as TradingSale;
use Yandex\Market\Utils;

/** @noinspection PhpUnused */
class AsdISale extends Skeleton
{
	use Concerns\HasMessage;
	use Concerns\HasOnce;

	public function getTitle()
	{
		return self::getMessage('TITLE');
	}

	public function getFields($siteId)
	{
		return $this->productData->getFields();
	}

	protected function requiredModules()
	{
		return [
			'asd.isale' => '4.3.15',
			'iblock',
		];
	}

	protected function fetchExists(TradingReference\Order $order, array $basketQuantities)
	{
		if ($order->getId() === null) { return []; }

		$result = [];

		$query = \CIsaleKeys::GetKeys([], [
			'ORDER_ID' => $order->getId(),
			'K_TYPE' => 'S',
		], [ 'ID', 'K_VALUE', 'PRODUCT_ID', 'TURN_REASON', 'PARAM_1' ]);

		while ($row = $query->Fetch())
		{
			if ($row['TURN_REASON'] === 'E' && $row['K_VALUE'] === '-') { continue; }

			$basketCode = $this->keyBasketCode($row, $order, $basketQuantities);

			if (!isset($basketQuantities[$basketCode])) { continue; }

			if (!isset($result[$basketCode])) { $result[$basketCode] = []; }

			$result[$basketCode][] = [
				'ID' => $row['ID'],
				'BASKET_CODE' => $basketCode,
				'CODE' => $row['K_VALUE'],
			];

			if (--$basketQuantities[$basketCode] <= 0)
			{
				unset($basketQuantities[$basketCode]);
			}
		}

		return $result;
	}

	protected function keyBasketCode(array $row, TradingReference\Order $order, array $basketQuantities)
	{
		if (!empty($row['PARAM_1'])) { return $row['PARAM_1']; }

		return $this->productBasketCode($row['PRODUCT_ID'], $order, $basketQuantities);
	}

	protected function makeBasketItemCodes(TradingReference\Order $order, $basketCode, array $iblockElement, $quantity)
	{
		if ($quantity <= 0) { return []; }

		$this->userEventBeforeReserveItem($order, $basketCode, $iblockElement);

		$result = $this->reserveStored($order, $basketCode, $quantity);

		if ($this->canGenerate($iblockElement))
		{
			$generated = $this->reserveGenerated($order, $basketCode, $quantity - count($result));
			$result = array_merge($result, $generated);
		}

		return $result;
	}

	protected function iblockElement(TradingReference\Order $order, $basketCode)
	{
		$element = parent::iblockElement($order, $basketCode);
		$allowedIblocks = array_map('intval', explode(',', Main\Config\Option::get('asd.isale', 'iblocks')));

		if (!in_array((int)$element['IBLOCK_ID'], $allowedIblocks, true))
		{
			throw new Main\SystemException(self::getMessage('IBLOCK_NOT_ALLOWED', [
				'#IBLOCK_ID#' => $element['IBLOCK_ID'],
				'#ALLOWED#' => implode(', ', $allowedIblocks),
			]));
		}

		return $element;
	}

	protected function userEventBeforeReserveItem(TradingReference\Order $order, $basketCode, array $iblockElement)
	{
		$events = GetModuleEvents('asd.isale', 'OnBeforeCheckBasketItem', true);

		if (empty($events) || !($order instanceof TradingSale\Order)) { return; }

		$basket = $order->getInternal()->getBasket();
		$basketItem = $basket !== null ? $basket->getItemByBasketCode($basketCode) : null;

		if ($basketItem === null) { return; }

		$orderData = $order->getInternal()->getFieldValues();
		$basketData = $basketItem->getFieldValues();

		foreach ($events as $event)
		{
			if (ExecuteModuleEventEx($event, [&$iblockElement, $orderData, &$basketData]) === false)
			{
				throw new Main\SystemException(self::getMessage(
					'USER_EVENT_REJECTED',
					$this->basketErrorVariables($order, $basketCode, $basketData) + [
						'#EVENT#' => Utils\Event::eventTitle($event),
					]
				));
			}
		}
	}

	protected function reserveStored(TradingReference\Order $order, $basketCode, $quantity)
	{
		if ($quantity <= 0) { return []; }

		$basketData = $order->getBasketItemData($basketCode)->getData();
		$productId = (int)$basketData['PRODUCT_ID'];
		$result = [];

		$query = \CIsaleKeys::GetKeys(
			['ID' => 'ASC'],
			[
				'PRODUCT_ID' => $productId,
				'K_TYPE' => 'S',
				'SHIPPED' => 'N',
				'TURN_REASON' => 'N',
			],
			['ID', 'K_VALUE']
		);

		while ($row = $query->Fetch())
		{
			$this->writeUpdate(
				$row['ID'],
				$this->keyReserveFields($order, $basketCode, $basketData),
				'reserveStored'
			);

			$result[] = [
				'ID' => $row['ID'],
				'CODE' => $row['K_VALUE'],
				'BASKET_CODE' => $basketCode,
			];

			if (--$quantity <= 0) { break; }
		}

		return $result;
	}

	protected function canGenerate(array $iblockElement)
	{
		$allowedIblocks = array_map('intval', explode(',', Main\Config\Option::get('asd.isale', 'iblocks_allow_generate')));

		return in_array((int)$iblockElement['IBLOCK_ID'], $allowedIblocks, true);
	}

	protected function reserveGenerated(TradingReference\Order $order, $basketCode, $quantity)
	{
		$basketData = $order->getBasketItemData($basketCode)->getData();
		$result = [];

		for ($i = 0; $i < $quantity; $i++)
		{
			$code = \CIsaleKeys::PinGenerate();
			$fields = [
				'K_VALUE' => $code,
				'K_TYPE' => 'S',
			];
			$fields += $this->keyReserveFields($order, $basketCode, $basketData);

			$addProvider = new \CIsaleKeys();
			$keyId = $addProvider->Add($fields);

			if (!$keyId)
			{
				throw new Main\SystemException(self::getMessage(
					'GENERATE_FAILED',
					$this->basketErrorVariables($order, $basketCode, $basketData)
				));
			}

			$result[] = [
				'ID' => $keyId,
				'CODE' => $code,
				'BASKET_CODE' => $basketCode,
			];
		}

		return $result;
	}

	protected function keyReserveFields(TradingReference\Order $order, $basketCode, array $basketData)
	{
		return [
			'PRODUCT_ID' => $basketData['PRODUCT_ID'],
			'PRODUCT_PRICE' => $basketData['PRICE'],
			'PRODUCT_DISCOUNT' => $basketData['DISCOUNT_PRICE'],
			'PRODUCT_CURRENCY' => $order->getCurrency(),
			'ORDER_ID' => $order->getId(),
			'OWNER_ID' => $order->getUserId(),
			'PARAM_1' => $basketCode,
			'TURN_REASON' => 'M',
			'TURN_READY_SEND' => 'Y',
		];
	}

	public function fail(TradingReference\Order $order, array $codes)
	{
		foreach ($codes as $code)
		{
			Assert::notNull($code['ID'], 'code[ID]');

			$this->writeUpdate($code['ID'], [
				'SHIPPED' => 'N',
				'TURN_REASON' => 'S',
				'TURN_READY_SEND' => 'N',
			], 'fail');
		}
	}

	public function ship(TradingReference\Order $order, array $codes)
	{
		$codeRows = $this->keyRows(array_column($codes, 'ID'));
		$sentKeys = [];

		foreach ($codes as $code)
		{
			Assert::notNull($code['ID'], 'code[ID]');

			$codeRow = $codeRows[$code['ID']];

			if ($codeRow['SHIPPED'] === 'Y') { continue; }

			$this->writeUpdate($code['ID'], [
				'SHIPPED' => 'Y',
				'SHIPPED_DATE' => 'NOW',
				'TURN_REASON' => 'N',
				'TURN_READY_SEND' => 'N',
			], 'ship');

			$sentKeys[] = [
				'K_ID' => $code['ID'],
			] + array_intersect_key($codeRow, [
				'K_VALUE' => true,
				'K_TYPE' => true,
				'PRODUCT_PRICE' => true,
				'PRODUCT_DISCOUNT' => true,
				'PRODUCT_CURRENCY' => true,
				'PRODUCT_ID' => true,
				'ORDER_ID' => true,
				'OWNER_ID' => true,
				'HASH' => true,
			]);
		}

		$this->fireSentKeysEvent($sentKeys);
	}

	protected function keyRows(array $ids, array $select = [])
	{
		if (empty($ids)) { return []; }

		$result = [];

		if (!empty($select)) { $select[] = 'ID'; }

		$query = \CIsaleKeys::GetKeys([], [ 'ID' => $ids ], $select);

		while ($row = $query->Fetch())
		{
			$result[$row['ID']] = $row;
		}

		return $result;
	}

	protected function fireSentKeysEvent(array $keys)
	{
		if (empty($keys)) { return; }

		foreach (GetModuleEvents('asd.isale', 'OnAfterSendKeys', true) as $event)
		{
			ExecuteModuleEventEx($event, [$keys]);
		}
	}

	protected function writeUpdate($id, array $data, $action)
	{
		$updateProvider = new \CIsaleKeys();
		$updated = $updateProvider->Update($id, $data);

		if (!$updated)
		{
			throw new Main\SystemException(self::getMessage('UPDATE_FAILED', [
				'#ID#' => $id,
				'#ACTION#' => $action,
				'#ERROR#' => $updateProvider->LAST_ERROR,
			]));
		}
	}
}