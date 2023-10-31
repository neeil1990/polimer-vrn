<?php

namespace Yandex\Market\Trading\Entity\Reference;

use Yandex\Market;

class ContactRegistry
{
	protected $environment;
	protected $anonymous;

	public function __construct(Environment $environment)
	{
		$this->environment = $environment;
	}

	public function getAnonymous($serviceCode, $personTypeId)
	{
		$key = $serviceCode . ':' . $personTypeId;

		if (!isset($this->anonymous[$key]))
		{
			$this->anonymous[$key] = $this->createAnonymous($serviceCode, $personTypeId);
		}

		return $this->anonymous[$key];
	}

	/**
	 * @param string $serviceCode
	 * @param int $personTypeId
	 *
	 * @return Contact
	 */
	protected function createAnonymous($serviceCode, $personTypeId)
	{
		throw new Market\Exceptions\NotImplementedMethod(static::class, 'createAnonymous');
	}

	public function getContact($personTypeId, array $properties = [])
	{
		return $this->createContact($personTypeId, $properties);
	}

	/**
	 * @param int $personTypeId
	 * @param array $properties
	 *
	 * @return Contact
	 */
	protected function createContact($personTypeId, array $properties)
	{
		throw new Market\Exceptions\NotImplementedMethod(static::class, 'createContact');
	}
}
