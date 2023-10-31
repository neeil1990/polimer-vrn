<?php

namespace Yandex\Market\Trading\Entity\Reference;

use Yandex\Market;

abstract class UserRegistry
{
	protected $environment;
	protected $anonymousUsers = [];
	protected $users = [];

	public function __construct(Environment $environment)
	{
		$this->environment = $environment;
	}

	public function getAnonymousUser($serviceCode, $siteId)
	{
		$key = $serviceCode . ':' . $siteId;

		if (!isset($this->anonymousUsers[$key]))
		{
			$this->anonymousUsers[$key] = $this->createAnonymousUser($serviceCode, $siteId);
		}

		return $this->anonymousUsers[$key];
	}

	/**
	 * @param string $serviceCode
	 * @param string $siteId
	 *
	 * @return User
	 */
	protected function createAnonymousUser($serviceCode, $siteId)
	{
		throw new Market\Exceptions\NotImplementedEntity(static::class, 'AnonymousUser');
	}

	public function getUser(array $data)
	{
		$primary = isset($data['ID']) ? $data['ID'] : null;

		if ($primary === null)
		{
			$result = $this->createUser($data);
		}
		else if (isset($this->users[$primary]))
		{
			$result = $this->users[$primary];
		}
		else
		{
			$result = $this->createUser($data);
			$this->users[$primary] = $result;
		}

		return $result;
	}

	/**
	 * @param array $data
	 *
	 * @return User
	 */
	protected function createUser(array $data)
	{
		throw new Market\Exceptions\NotImplementedEntity(static::class, 'User');
	}
}