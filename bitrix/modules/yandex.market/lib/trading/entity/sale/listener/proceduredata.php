<?php

namespace Yandex\Market\Trading\Entity\Sale\Listener;

class ProcedureData
{
	protected $path;
	protected $payload;

	public function __construct($path, array $payload)
	{
		$this->path = $path;
		$this->payload = $payload;
	}

	public function getPath()
	{
		return $this->path;
	}

	public function getPayload()
	{
		return $this->payload;
	}

	public function getCacheSign()
	{
		return $this->path . ':' . serialize($this->payload);
	}

	public function __toString()
	{
		return $this->getCacheSign();
	}
}