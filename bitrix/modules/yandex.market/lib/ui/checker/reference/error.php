<?php

namespace Yandex\Market\Ui\Checker\Reference;

use Yandex\Market;

class Error extends Market\Error\Base
{
	protected $group;
	protected $groupUrl;
	protected $count;
	protected $description;

	/** @return string|null */
	public function getDescription()
	{
		return $this->description;
	}

	/** @param string $description */
	public function setDescription($description)
	{
		$this->description = $description;
	}

	/** @return string|null */
	public function getGroup()
	{
		return $this->group;
	}

	/** @return string|null */
	public function getGroupUrl()
	{
		return $this->groupUrl;
	}

	/**
	 * @param string $group
	 * @param string|null $url
	 */
	public function setGroup($group, $url = null)
	{
		$this->group = $group;
		$this->groupUrl = $url;
	}

	/** @return int|null */
	public function getCount()
	{
		return $this->count;
	}

	/** @param int $count */
	public function setCount($count)
	{
		$this->count = (int)$count;
	}
}