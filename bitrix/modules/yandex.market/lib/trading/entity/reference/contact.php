<?php

namespace Yandex\Market\Trading\Entity\Reference;

use Yandex\Market;
use Bitrix\Main;

class Contact
{
	protected $environment;
	protected $personTypeId;
	protected $properties;
	protected $id;

	public function __construct(Environment $environment, $personTypeId, array $properties = [])
	{
		$this->environment = $environment;
		$this->personTypeId = $personTypeId;
		$this->properties = $properties;
 	}

	public function isInstalled()
	{
		$matched = $this->getId();

		return !empty($matched);
	}

	/** @return int[] */
	public function getId()
	{
		if ($this->id === null)
		{
			$this->id = $this->search();
		}

		return $this->id;
	}

	protected function search()
	{
		throw new Market\Exceptions\NotImplementedMethod(static::class, 'search');
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
}
