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
	 * ����� �������
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
	 * ������ �� ���������
	 *
	 * @return string[]
	 */
	public function getDefaults()
	{
		throw new Market\Exceptions\NotImplementedMethod(static::class, 'getDefaults');
	}

	/**
	 * ����� ����� ������
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
	 * ���� �� ��������� ��� ������������� �������
	 *
	 * @return string
	 */
	public function getWarehouseDefaultField()
	{
		throw new Market\Exceptions\NotImplementedMethod(static::class, 'getWarehouseDefaultField');
	}

	/**
	 * ���� �� ��������� ��� ������������� ������� ������
	 *
	 * @return string
	 */
	public function getOutletDefaultField()
	{
		throw new Market\Exceptions\NotImplementedMethod(static::class, 'getOutletDefaultField');
	}

	/**
	 * ��� ���� �������
	 *
	 * @param string $field
	 *
	 * @return array<int, string>
	 */
	public function existsStores($field)
	{
		throw new Market\Exceptions\NotImplementedMethod(static::class, 'existsStores');
	}

	/**
	 * ���� ������� �� ��������������
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
	 * �������������� ������� �� ����
	 *
	 * @param string $field
	 * @param string $value
	 *
	 * @return int[]|string[]
	 */
	public function findStores($field, $value)
	{
		throw new Market\Exceptions\NotImplementedMethod(static::class, 'findStores');
	}

	/**
	 * ������������� ������ �� ����
	 *
	 * @param string $field
	 * @param string $value
	 *
	 * @return int|string|null
	 */
	public function findStore($field, $value)
	{
		$stores = $this->findStores($field, $value);

		return !empty($stores) ? reset($stores) : null;
	}

	/**
	 * ������ ���������� �������
	 *
	 * @param string[] $stores
	 * @param Main\Type\DateTime|null $date
	 * @param int|null $offset
	 * @param int $limit
	 *
	 * @return int[]
	 */
	public function getChanged($stores, Main\Type\DateTime $date = null, $offset = null, $limit = 500)
	{
		throw new Market\Exceptions\NotImplementedMethod(static::class, 'getChanged');
	}

	/**
	 * ������� ������ �� ��������� �������
	 *
	 * @param string[] $stores
	 * @param int[] $productIds
	 *
	 * @return array{
	 *  ID: int,
	 *  QUANTITY: float|null,
	 *  QUANTITY_LIST: array<string, float>|null
	 * }[]
	 */
	public function getAmounts($stores, $productIds)
	{
		throw new Market\Exceptions\NotImplementedMethod(static::class, 'getAmounts');
	}

	/**
	 * ������������ ����������� ���������� ����������
	 *
	 * @param string[] $stores
	 * @param int[] $productIds
	 *
	 * @return array<int, float>
	 */
	public function getLimits($stores, $productIds)
	{
		throw new Market\Exceptions\NotImplementedMethod(static::class, 'getAmounts');
	}

	/**
	 * ������ �� ������� ��� ������������ �������
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