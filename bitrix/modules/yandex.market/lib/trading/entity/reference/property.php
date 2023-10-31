<?php

namespace Yandex\Market\Trading\Entity\Reference;

use Yandex\Market;
use Bitrix\Main;

abstract class Property
{
	protected $environment;

	public function __construct(Environment $environment)
	{
		$this->environment = $environment;
	}

	/**
	 * Набор свойств заказа
	 *
	 * @param int $personTypeId
	 *
	 * @return array{ID: string, VALUE: string, TYPE: string|null}[]
	 */
	public function getEnum($personTypeId)
	{
		throw new Market\Exceptions\NotImplementedMethod(static::class, 'getEnum');
	}

	/**
	 * Создать свойство
	 *
	 * @param int $personTypeId
	 * @param array $fields
	 *
	 * @return Main\Entity\AddResult
	 */
	public function add($personTypeId, $fields)
	{
		throw new Market\Exceptions\NotImplementedMethod(static::class, 'add');
	}

	/**
	 * Обновить свойство
	 *
	 * @param int $propertyId
	 * @param array $fields
	 *
	 * @return Main\Entity\UpdateResult
	 */
	public function update($propertyId, $fields)
	{
		throw new Market\Exceptions\NotImplementedMethod(static::class, 'update');
	}

	/**
	 * @param int $propertyId
	 *
	 * @return string
	 */
	public function getEditUrl($propertyId)
	{
		throw new Market\Exceptions\NotImplementedMethod(static::class, 'getEditUrl');
	}

	/**
	 * Преобразование массива значений формата array<TYPE, VALUE> в array<PROPERTY_ID, VALUE>
	 *
	 * @param int $personTypeId
	 * @param array<string, mixed> $values
	 *
	 * @return array<string, mixed>
	 */
	public function convertMeaningfulValues($personTypeId, array $values)
	{
		throw new Market\Exceptions\NotImplementedMethod(static::class, 'convertMeaningfulValues');
	}

	/**
	 * @param int $personTypeId
	 * @param array<string, mixed> $values
	 *
	 * @return array<string, mixed>
	 */
	public function formatMeaningfulValues($personTypeId, array $values)
	{
		throw new Market\Exceptions\NotImplementedMethod(static::class, 'formatMeaningfulValues');
	}
}