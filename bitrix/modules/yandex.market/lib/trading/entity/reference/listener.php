<?php

namespace Yandex\Market\Trading\Entity\Reference;

use Yandex\Market;

abstract class Listener
{
	protected $environment;

	public static function getClassName()
	{
		return '\\' . static::class;
	}

	public function __construct(Environment $environment)
	{
		$this->environment = $environment;
	}

	public function bind()
	{
		$handlers = $this->getEventHandlers();

		Market\Utils\Event::bind(static::getClassName(), $handlers);
	}

	public function unbind()
	{
		$handlers = $this->getEventHandlers();

		Market\Utils\Event::unbind(static::getClassName(), $handlers);
	}

	protected function getEventHandlers()
	{
		return [];
	}
}