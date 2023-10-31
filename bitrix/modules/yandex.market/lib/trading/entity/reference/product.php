<?php

namespace Yandex\Market\Trading\Entity\Reference;

use Yandex\Market;
use Bitrix\Main;

abstract class Product
{
	protected $environment;

	public function __construct(Environment $environment)
	{
		$this->environment = $environment;
	}

	/**
	 * Набор полей для сопоставления идентификаторов товара с offerId сервиса
	 *
	 * @param $iblockId
	 *
	 * @return array{ID: string, VALUE: string}[]
	 */
	public function getFieldEnum($iblockId)
	{
		throw new Market\Exceptions\NotImplementedMethod(static::class, 'getFieldEnum');
	}

	/**
	 * Соответствие идентификатора товара и offerId сервиса
	 *
	 * @param int[] $productIds
	 * @param array{IBLOCK: string, FIELD: string}[] $skuMap
	 *
	 * @return array<int, string> bitrixProductId => serviceOfferId
	 */
	public function getSkuMap($productIds, $skuMap)
	{
		throw new Market\Exceptions\NotImplementedMethod(static::class, 'getSkuMap');
	}

	/**
	 * Соответствие offerId сервиса и идентификатора товара
	 *
	 * @param string[] $offerIds
	 * @param array{IBLOCK: string, FIELD: string}[] $skuMap
	 *
	 * @return array<string, int> serviceOfferId => bitrixProductId
	 */
	public function getOfferMap($offerIds, $skuMap)
	{
		throw new Market\Exceptions\NotImplementedMethod(static::class, 'getOfferMap');
	}

	/**
	 * Отладочные данные по товарам
	 *
	 * @param int[] $productIds
	 *
	 * @return array<int, array>
	 */
	public function debugBasketData($productIds)
	{
		return [];
	}

	/**
	 * Данные по товарам для формирования корзины
	 *
	 * @param int[] $productIds
	 * @param array<int, float[]>|null $quantities
	 * @param array $context
	 *
	 * @return array<int|string, array>
	 */
	public function getBasketData($productIds, $quantities = null, array $context = [])
	{
		return [];
	}

	/**
	 * Тип маркировки товара
	 *
	 * @param string $code
	 *
	 * @return string
	 */
	public function getMarkingGroupType($code)
	{
		return null;
	}
}