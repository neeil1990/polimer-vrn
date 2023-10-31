<?php

namespace Yandex\Market\Trading\Entity\Reference;

use Yandex\Market;
use Bitrix\Main;

abstract class PlatformRegistry
{
	protected $environment;
	protected $platforms = [];

	public function __construct(Environment $environment)
	{
		$this->environment = $environment;
	}

	/**
	 * @param $serviceCode
	 * @param $siteId
	 * @return Platform
	 */
	public function getPlatform($serviceCode, $siteId)
	{
		$key = $serviceCode . ':' . $siteId;

		if (!isset($this->platforms[$key]))
		{
			$this->platforms[$key] = $this->createPlatform($serviceCode, $siteId);
		}

		return $this->platforms[$key];
	}

	/**
	 * @param string $serviceCode
	 * @param string $siteId
	 * @return Platform
	 */
	protected function createPlatform($serviceCode, $siteId)
	{
		throw new Market\Exceptions\NotImplementedEntity(static::class, 'Platform');
	}
}