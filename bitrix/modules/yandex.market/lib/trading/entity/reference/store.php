<?php

namespace Yandex\Market\Trading\Entity\Reference;

use Yandex\Market;
use Bitrix\Main;

abstract class Store
{
	protected $environment;

	public function __construct(Environment $environment)
	{
		$this->environment = $environment;
	}

	/**
	 * Набор складов
	 *
	 * @param string|null $siteId
	 *
	 * @return array{ID: string, VALUE: string}[]
	 */
	public function getEnum($siteId = null)
	{
		throw new Market\Exceptions\NotImplementedMethod(static::class, 'getEnum');
	}

	/**
	 * Склады по умолчанию
	 *
	 * @return string[]
	 */
	public function getDefaults()
	{
		throw new Market\Exceptions\NotImplementedMethod(static::class, 'getDefaults');
	}

	/**
	 * Набор полей склада
	 *
	 * @param string|null $siteId
	 *
	 * @return array{ID: string, VALUE: string}[]
	 */
	public function getFieldEnum($siteId = null)
	{
		throw new Market\Exceptions\NotImplementedMethod(static::class, 'getFieldEnum');
	}

	/**
	 * Поле по умолчанию для сопоставления пунктов выдачи
	 *
	 * @return string
	 */
	public function getOutletDefaultField()
	{
		throw new Market\Exceptions\NotImplementedMethod(static::class, 'getOutletDefaultField');
	}

	/**
	 * Коды складов по идентификатору
	 *
	 * @param string $field
	 * @param int[] $ids
	 *
	 * @return array<int, string>
	 */
	public function mapStores($field, $ids)
	{
		throw new Market\Exceptions\NotImplementedMethod(static::class, 'mapStores');
	}

	/**
	 * Идентификатор склада по коду
	 *
	 * @param string $field
	 * @param string $value
	 *
	 * @return int|string
	 */
	public function findStore($field, $value)
	{
		throw new Market\Exceptions\NotImplementedMethod(static::class, 'findStore');
	}

	/**
	 * Наличие товара на выбранных складах
	 *
	 * @param string[] $stores
	 * @param int[] $productIds
	 *
	 * @return array{
	 *  ID: int,
	 *  TIMESTAMP_X: Main\Type\DateTime,
	 *  QUANTITY: float|null,
	 *  QUANTITY_LIST: array<string, float>|null
	 * }[]
	 */
	public function getAmounts($stores, $productIds)
	{
		throw new Market\Exceptions\NotImplementedMethod(static::class, 'getAmounts');
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
}