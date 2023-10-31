<?php

namespace Yandex\Market\Trading\Entity\Reference;

use Yandex\Market;
use Bitrix\Main;

abstract class User
{
	protected $environment;
	protected $data;
	/** @var array|null */
	protected $searchOnly;

	public function __construct(Environment $environment, $data)
	{
		$this->environment = $environment;
		$this->data = $data;
	}

	public function checkInstall()
	{
		// nothing by default
	}

	public function isInstalled()
	{
		return ($this->getId() > 0);
	}

	/**
	 * @param array $data
	 *
	 * @return Main\Entity\AddResult
	 */
	public function install(array $data = [])
	{
		throw new Market\Exceptions\NotImplementedMethod(static::class, 'install');
	}

	/**
	 * @param array $data
	 *
	 * @return Main\Entity\UpdateResult
	 */
	public function update(array $data)
	{
		throw new Market\Exceptions\NotImplementedMethod(static::class, 'update');
	}

	/**
	 * @param string $code
	 *
	 * @return Main\Entity\UpdateResult
	 */
	public function migrate($code)
	{
		throw new Market\Exceptions\NotImplementedMethod(static::class, 'migrate');
	}

	/**
	 * @param int $groupId
	 *
	 * @return Main\Result
	 */
	public function attachGroup($groupId)
	{
		throw new Market\Exceptions\NotImplementedMethod(static::class, 'attachGroup');
	}

	/**
	 * @return int|null
	 */
	public function getId()
	{
		throw new Market\Exceptions\NotImplementedMethod(static::class, 'getId');
	}

	public function searchOnly(array $fields)
	{
		$this->searchOnly = $fields;
	}

	protected function canSearch($field)
	{
		return $this->searchOnly === null || in_array($field, $this->searchOnly, true);
	}
}