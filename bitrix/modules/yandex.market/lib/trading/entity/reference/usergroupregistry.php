<?php

namespace Yandex\Market\Trading\Entity\Reference;

use Yandex\Market;

abstract class UserGroupRegistry
{
	protected $environment;
	protected $groups = [];

	public function __construct(Environment $environment)
	{
		$this->environment = $environment;
	}

	/**
	 * @param $serviceCode
	 * @param $siteId
	 * @return UserGroup
	 */
	public function getGroup($serviceCode, $siteId)
	{
		$key = $serviceCode . ':' . $siteId;

		if (!isset($this->groups[$key]))
		{
			$this->groups[$key] = $this->createGroup($serviceCode, $siteId);
		}

		return $this->groups[$key];
	}

	/**
	 * @param $serviceCode
	 * @param $siteId
	 *
	 * @return UserGroup
	 */
	protected function createGroup($serviceCode, $siteId)
	{
		throw new Market\Exceptions\NotImplementedEntity(static::class, 'UserGroup');
	}
}