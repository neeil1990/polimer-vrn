<?php

namespace Yandex\Market\Api\Model;

use Yandex\Market;
use Bitrix\Main;

class Region extends Market\Api\Reference\Model
{
	public function getId()
	{
		return (int)$this->getRequiredField('id');
	}

	public function getName()
	{
		return (string)$this->getRequiredField('name');
	}

	public function getType()
	{
		return (string)$this->getRequiredField('type');
	}

	/**
	 * @return Region|null
	 */
	public function getParent()
	{
		return $this->getChildModel('parent');
	}

	protected function getChildModelReference()
	{
		return [
			'parent' => static::class,
		];
	}
}