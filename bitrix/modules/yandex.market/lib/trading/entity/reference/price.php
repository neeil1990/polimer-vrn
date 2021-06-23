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