<?php

namespace Yandex\Market\Trading\Entity\Common\Digital;

use Bitrix\Main;
use Yandex\Market\Reference\Concerns;
use Yandex\Market\Trading\Entity\Reference as TradingReference;
use Yandex\Market\Trading\Entity\Reference\Order;

/** @noinspection PhpUnused */
abstract class Skeleton extends TradingReference\Digital
{
	use Concerns\HasMessage;

	protected $productData;

	public function __construct(TradingReference\Environment $environment, array $settings = [])
	{
		parent::__construct($environment, $settings);

		$this->productData = new Data\ProductProperty($settings);
	}

	public function exists(Order $order, array $basketQuantities)
	{
		$allExists = $this->fetchExists($order, $basketQuantities);
		$result = [];

		foreach ($basketQuantities as $basketCode => $quantity)
		{
			$iblockElement = $this->iblockElement($order, $basketCode);

			$activateTill = $this->productData->tryActivateTill($iblockElement);
			$slip = $this->productData->trySlip($iblockElement);

			$codes = isset($allExists[$basketCode]) ? array_values($allExists[$basketCode]) : [];
			$codes += array_fill(0, $quantity, [ 'BASKET_CODE' => $basketCode ]);
			$codes = $this->extendCodes($codes, [
				'SLIP' => $slip,
				'ACTIVATE_TILL' => $activateTill,
			]);

			if (empty($codes)) { continue; }

			array_push($result, ...$codes);
		}

		return $result;
	}

	public function reserve(TradingReference\Order $order, array $basketQuantities)
	{
		$allExists = $this->fetchExists($order, $basketQuantities);
		$result = [];

		foreach ($basketQuantities as $basketCode => $quantity)
		{
			$iblockElement = $this->iblockElement($order, $basketCode);

			$activateTill = $this->productData->activateTill($iblockElement);
			$slip = $this->productData->slip($iblockElement);

			$exists = isset($allExists[$basketCode]) ? $allExists[$basketCode] : [];
			$new = $this->makeBasketItemCodes($order, $basketCode, $iblockElement, $quantity - count($exists));
			$codes = array_merge($exists, $new);
			$codes = $this->extendCodes($codes, [
				'SLIP' => $slip,
				'ACTIVATE_TILL' => $activateTill,
			]);

			$this->testBasketItemCodes($order, $basketCode, $quantity, $codes);

			if (empty($codes)) { continue; }

			array_push($result, ...$codes);
		}

		return $result;
	}

	abstract protected function fetchExists(TradingReference\Order $order, array $basketQuantities);

	protected function iblockElement(TradingReference\Order $order, $basketCode)
	{
		$basketData = $order->getBasketItemData($basketCode)->getData();
		$productId = (int)$basketData['PRODUCT_ID'];
		$element = \CIBlockElement::GetByID($productId)->Fetch();

		if (!$element)
		{
			throw new Main\SystemException(self::getMessage('ELEMENT_NOT_FOUND', $this->basketErrorVariables($order, $basketCode, $basketData)));
		}

		return $element;
	}

	protected function productBasketCode($productId, TradingReference\Order $order, array $basketQuantities)
	{
		$productId = (int)$productId;

		if ($productId <= 0) { return null; }

		$result = null;

		foreach ($basketQuantities as $basketCode => $quantity)
		{
			$basketData = $order->getBasketItemData($basketCode)->getData();

			if (isset($basketData['PRODUCT_ID']) && (int)$basketData['PRODUCT_ID'] === $productId)
			{
				$result = $basketCode;
				break;
			}
		}

		return $result;
	}

	abstract protected function makeBasketItemCodes(TradingReference\Order $order, $basketCode, array $iblockElement, $quantity);

	protected function testBasketItemCodes(TradingReference\Order $order, $basketCode, $quantity, array $codes)
	{
		if (count($codes) >= $quantity) { return; }

		throw new Main\SystemException(self::getMessage('NOT_ENOUGH_CODES', $this->basketErrorVariables($order, $basketCode) + [
			'#REQUIRED#' => $quantity,
			'#FOUND#' => count($codes),
		]));
	}

	protected function extendCodes(array $codes, array $additional)
	{
		foreach ($codes as &$code)
		{
			$code += $additional;
		}
		unset($code);

		return $codes;
	}

	protected function basketErrorVariables(TradingReference\Order $order, $basketCode, array $basketData = null)
	{
		if ($basketData === null) { $basketData = $order->getBasketItemData($basketCode)->getData(); }

		return [
			'#BASKET_ITEM#' => self::getMessage('BASKET_ITEM_MARKER', [
				'#BASKET_CODE#' => $basketCode,
				'#BASKET_NAME#' => $basketData['NAME'],
				'#PRODUCT_ID#' => $basketData['PRODUCT_ID'],
			]),
		];
	}
}