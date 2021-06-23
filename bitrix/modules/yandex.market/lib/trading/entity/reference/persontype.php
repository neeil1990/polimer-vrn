<?php

namespace Yandex\Market\Trading\Entity\Reference;

use Yandex\Market;
use Bitrix\Main;

class PersonType
{
	protected $environment;

	public function __construct(Environment $environment)
	{
		$this->environment = $environment;
	}

	/**
	 * @param string|null $siteId
	 *
	 * @return array{ID: string, VALUE: string}[]
	 */
	public function getEnum($siteId = null)
	{
		throw new Market\Exceptions\NotImplementedMethod(static::class, 'getEnum');
	}

	/**
	 * @param string|null $siteId
	 *
	 * @return int|null
	 */
	public function getIndividualId($siteId = null)
	{
		throw new Market\Exceptions\NotImplementedMethod(static::class, 'getIndividualId');
	}

	/**
	 * @param string|null $siteId
	 *
	 * @return int|null
	 */
	public function getLegalId($siteId = null)
	{
		throw new Market\Exceptions\NotImplementedMethod(static::class, 'getLegalId');
	}
}