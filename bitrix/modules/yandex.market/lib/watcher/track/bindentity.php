<?php
namespace Yandex\Market\Watcher\Track;

class BindEntity
{
	/** @var string */
	private $type;
	/** @var string|null */
	private $group;
	/** @var string|null */
	private $replaceType;
	/** @var int|null */
	private $setupId;

	public function __construct($type, $group = null, $replaceType = null, $setupId = null)
	{
		$this->type = $type;
		$this->group = $group;
		$this->replaceType = $replaceType;
		$this->setupId = $setupId;
	}

	public function type()
	{
		return $this->type;
	}

	public function group()
	{
		return $this->group;
	}

	public function replaceType()
	{
		return $this->replaceType;
	}

	public function setupId()
	{
		return $this->setupId;
	}
}