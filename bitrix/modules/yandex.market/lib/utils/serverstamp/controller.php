<?php

namespace Yandex\Market\Utils\ServerStamp;

use Yandex\Market;

class Controller
{
	/** @var PropertyInterface[] */
	protected $properties;

	public function __construct()
	{
		$this->properties = [
			new Development(),
			new Database(),
			new DocumentRoot(),
		];
	}

	public function properties()
	{
		return $this->properties;
	}

	public function reset()
	{
		$this->resetStored();
		$this->resetProperties();
	}

	protected function resetStored()
	{
		Market\State::remove('server_stamp');
	}

	protected function resetProperties()
	{
		foreach ($this->properties as $property)
		{
			$property->reset();
		}
	}

	public function check()
	{
		$state = $this->stored();
		$disabled = [];

		foreach ($this->properties as $property)
		{
			$name = $property->name();

			if ($this->disabled($name))
			{
				$disabled[$name] = true;
				continue;
			}

			$current = $property->collect();

			if ($current === null) { continue; }

			if (isset($state[$name]))
			{
				$property->test($state[$name], $current);
			}

			$state[$name] = $current;
		}

		$this->save(array_diff_key($state, $disabled));
	}

	protected function disabled($name)
	{
		return Market\Config::getOption('server_stamp_disable_' . $name, 'N') === 'Y';
	}

	protected function stored()
	{
		$stored = (string)Market\State::get('server_stamp');

		if ($stored === '') { return []; }

		$stored = unserialize($stored);

		if (!is_array($stored)) { return []; }

		return $stored;
	}

	protected function save(array $state)
	{
		Market\State::set('server_stamp', serialize($state));
	}
}