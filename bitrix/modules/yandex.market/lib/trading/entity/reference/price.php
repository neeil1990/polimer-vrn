<?php

namespace Yandex\Market\Trading\Entity\Reference;

use Yandex\Market;
use Bitrix\Main;

abstract class Price
{
	protected $environment;

	public function __construct(Environment $environment)
	{
		$this->environment = $environment;
	}

	/**
	 * Набор источников цен
	 *
	 * @return array{ID: string, VALUE: string}[]
	 */
	public function getSourceEnum()
	{
		throw new Market\Exceptions\NotImplementedMethod(static::class, 'getSourceEnum');
	}

	/**
	 * Набор типов цен
	 *
	 * @return array{ID: string, VALUE: string}[]
	 */
	public function getTypeEnum()
	{
		throw new Market\Exceptions\NotImplementedMethod(static::class, 'getTypeEnum');
	}

	/**
	 * Типы цен по умолчанию для групп пользователей
	 *
	 * @param int[]|null $userGroups
	 *
	 * @return string[]
	 */
	public function getTypeDefaults(array $userGroups = null)
	{
		throw new Market\Exceptions\NotImplementedMethod(static::class, 'getTypeDefaults');
	}

	/**
	 * Необходимо ли выполнить полный обход
	 *
	 * @param Main\Type\DateTime $date
	 * @param array $context
	 *
	 * @return bool
	 */
	public function needRefresh(Main\Type\DateTime $date, array $context = [])
	{
		throw new Market\Exceptions\NotImplementedMethod(static::class, 'needRefresh');
	}

	/**
	 * Список измененных товаров
	 *
	 * @param array[] $context
	 * @param Main\Type\DateTime|null $date
	 * @param int|null $offset
	 * @param int $limit
	 *
	 * @return int[]
	 */
	public function getChanged(array $context = [], Main\Type\DateTime $date = null, $offset = null, $limit = 500)
	{
		throw new Market\Exceptions\NotImplementedMethod(static::class, 'getChanged');
	}

	/**
	 * Цены на товары
	 *
	 * @param int[] $productIds
	 * @param array<int, float[]>|null $quantities
	 * @param array $context
	 *
	 * @return array{ID: int, PRICE: float, BASE_PRICE: float, CURRENCY: string}[]
	 */
	public function getPrices($productIds, $quantities = null, array $context = [])
	{
		throw new Market\Exceptions\NotImplementedMethod(static::class, 'getPrices');
	}

	/**
	 * Данные по ценам товаров для формирования корзины
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