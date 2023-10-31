<?php
namespace Yandex\Market\Data\Run;

use Bitrix\Main;

class Offset
{
	protected $offset = [];
	protected $index = [];
	protected $interrupted = false;

	public function __construct($offset, array $types)
	{
		$partials = explode(':', (string)$offset);

		foreach ($types as $index => $type)
		{
			$this->index[$type] = null;
			$this->offset[$type] = isset($partials[$index]) && $partials[$index] !== '' ? (int)$partials[$index] : null;
		}
	}

	public function __toString()
	{
		$result = '';

		foreach ($this->offset as $offset)
		{
			if ($offset === null) { break; }

			$result .= ($result !== '' ? ':' : '') . $offset;
		}

		return $result;
	}

	public function tick($type)
	{
		$this->assertType($type);

		if ($this->index[$type] === null)
		{
			$this->index[$type] = 0;
		}
		else
		{
			++$this->index[$type];
		}

		if ($this->offset[$type] === null || $this->offset[$type] < $this->index[$type])
		{
			$result = true;
			$this->offset[$type] = $this->index[$type];
			$this->resetNext($type);
		}
		else if ($this->offset[$type] === $this->index[$type])
		{
			$result = true;
		}
		else
		{
			$result = false;
		}

		return $result;
	}

	public function next($type)
	{
		$this->assertType($type);

		$this->offset[$type] = ++$this->offset[$type];
		$this->resetNext($type);
	}

	public function get($type)
	{
		return $this->offset[$type];
	}

	public function set($type, $offset)
	{
		$this->assertType($type);

		$this->offset[$type] = $offset;
		$this->resetNext($type);
	}

	protected function resetNext($after)
	{
		$found = false;

		foreach ($this->offset as $type => $unused)
		{
			if ($type === $after)
			{
				$found = true;
				continue;
			}

			if ($found)
			{
				$this->offset[$type] = null;
			}
			else if (!isset($this->offset[$type]))
			{
				$this->offset[$type] = 0;
			}
		}
	}

	public function interrupt()
	{
		$this->interrupted = true;
	}

	public function interrupted()
	{
		return $this->interrupted;
	}

	private function assertType($type)
	{
		if (!isset($this->offset[$type]) && !array_key_exists($type, $this->offset))
		{
			throw new Main\ArgumentException(sprintf('unknown offset type %s', $type));
		}
	}
}