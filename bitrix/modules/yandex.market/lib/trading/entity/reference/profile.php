<?php

namespace Yandex\Market\Trading\Entity\Reference;

use Yandex\Market;
use Bitrix\Main;

abstract class Profile
{
	protected $environment;

	public function __construct(Environment $environment)
	{
		$this->environment = $environment;
	}

	/**
	 * Набор профилей пользователя
	 *
	 * @param int $userId
	 * @param int $personTypeId
	 *
	 * @return array{ID: string, VALUE: string}[]
	 */
	public function getEnum($userId, $personTypeId)
	{
		throw new Market\Exceptions\NotImplementedMethod(static::class, 'getEnum');
	}

	/**
	 * Поиск профиля покупателя
	 *
	 * @param int $userId
	 * @param int $personTypeId
	 * @param array $rawValues
	 *
	 * @return int|null
	 */
	public function searchRaw($userId, $personTypeId, array $rawValues)
	{
		throw new Market\Exceptions\NotImplementedMethod(static::class, 'searchRaw');
	}

	/**
	 * @deprecated
	 * @see add
	 *
	 * @param int $userId
	 * @param int $personTypeId
	 * @param array $values
	 *
	 * @return int
	 * @throws Main\SystemException
	 */
	public function createProfile($userId, $personTypeId, array $values = [])
	{
		$addResult = $this->add($userId, $personTypeId, $values);
		Market\Result\Facade::handleException($addResult);

		return $addResult->getId();
	}

	/**
	 * Создание профиля
	 *
	 * @param int $userId
	 * @param int $personTypeId
	 * @param array $values
	 *
	 * @return Main\Entity\AddResult
	 */
	public function add($userId, $personTypeId, array $values = [])
	{
		throw new Market\Exceptions\NotImplementedMethod(static::class, 'add');
	}

	/**
	 * Создание профиля с набором значений свойств
	 *
	 * @param int         $userId
	 * @param int         $personTypeId
	 * @param string|null $profileName
	 * @param array       $rawValues
	 *
	 * @return Main\Entity\AddResult
	 */
	public function addRaw($userId, $personTypeId, $profileName, array $rawValues = [])
	{
		throw new Market\Exceptions\NotImplementedMethod(static::class, 'addRaw');
	}

	/**
	 * Обновление записи профиля
	 *
	 * @param int $profileId
	 * @param array $values
	 *
	 * @return Main\Entity\UpdateResult
	 */
	public function update($profileId, array $values)
	{
		throw new Market\Exceptions\NotImplementedMethod(static::class, 'update');
	}

	/**
	 * Обновление записи профиля с набором значений свойств
	 *
	 * @param int         $profileId
	 * @param string|null $profileName
	 * @param array       $rawValues
	 *
	 * @return Main\Entity\UpdateResult
	 */
	public function updateRaw($profileId, $profileName, array $rawValues = [])
	{
		throw new Market\Exceptions\NotImplementedMethod(static::class, 'updateRaw');
	}

	/**
	 * Значения профиля
	 *
	 * @param int $profileId
	 *
	 * @return array<int, mixed>
	 */
	public function getValues($profileId)
	{
		throw new Market\Exceptions\NotImplementedMethod(static::class, 'getValues');
	}

	/**
	 * Адрес страницы редактирования профиля
	 *
	 * @param int $profileId
	 *
	 * @return string
	 */
	public function getEditUrl($profileId)
	{
		throw new Market\Exceptions\NotImplementedMethod(static::class, 'getEditUrl');
	}
}