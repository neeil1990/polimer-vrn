<?php

namespace Yandex\Market\Trading\Service\Reference;

use Yandex\Market;
use Bitrix\Main;

abstract class Status
{
	protected $provider;

	public function __construct(Provider $provider)
	{
		$this->provider = $provider;
	}

	/**
	 * @param string $status
	 * @param string $version
	 *
	 * @return string
	 */
	abstract public function getTitle($status, $version = '');

	/**
	 * @return string[]
	 */
	abstract public function getVariants();
}