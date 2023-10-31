<?php
namespace Yandex\Market\Data\Run;

class Waterfall
{
	/** @var callable[] */
	protected $stages = [];
	/** @var callable|null */
	protected $afterCallable;
	protected $index = 0;

	public function add(callable $stage)
	{
		$this->stages[] = $stage;

		return $this;
	}

	public function after(callable $after)
	{
		$this->afterCallable = $after;

		return $this;
	}

	public function __invoke(callable $next, ...$arguments)
	{
		$this->after($next);
		$this->run(...$arguments);
	}

	public function run(...$arguments)
	{
		$this->index = 0;
		$this->next(...$arguments);

		return $this;
	}

	public function next(...$arguments)
	{
		if (!isset($this->stages[$this->index]))
		{
			if ($this->afterCallable !== null)
			{
				$afterCallable = $this->afterCallable;
				$afterCallable(...$arguments);
			}

			return;
		}

		$stage = $this->stages[$this->index];

		++$this->index;
		$stage([$this, 'next'], ...$arguments);
		--$this->index;
	}
}