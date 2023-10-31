<?php

namespace Yandex\Market\Trading\Service\Common\Concerns\Action;

trait HasChangesTrait
{
	protected $changes = [];

	protected function resetChanges()
	{
		return $this->changes = [];
	}

	protected function pushChange($key, $value)
	{
		$this->changes[$key] = $value;
	}

	protected function hasChanges()
	{
		return !empty($this->changes);
	}

	protected function getChange($key)
	{
		return isset($this->changes[$key]) ? $this->changes[$key] : null;
	}
}