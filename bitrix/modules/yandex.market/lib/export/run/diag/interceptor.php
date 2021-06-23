<?php

namespace Yandex\Market\Export\Run\Diag;

use Bitrix\Main;

class Interceptor
{
	protected $callback;
	protected $originalHandlerOutput;

	public function __construct(\Closure $callback)
	{
		$this->callback = $callback;
		$this->originalHandlerOutput = $this->getApplication()->createExceptionHandlerOutput();
	}

	public function bind()
	{
		if (!class_exists(OutputHandler::class)) { return; }

		$exceptionHandler = $this->getApplication()->getExceptionHandler();
		$outputHandler = new OutputHandler($this->callback, $this->originalHandlerOutput);

		$exceptionHandler->setHandlerOutput($outputHandler);
	}

	public function unbind()
	{
		if (!class_exists(OutputHandler::class)) { return; }

		$exceptionHandler = $this->getApplication()->getExceptionHandler();

		$exceptionHandler->setHandlerOutput($this->originalHandlerOutput);
	}

	/** @return Main\Application */
	protected function getApplication()
	{
		return Main\Application::getInstance();
	}
}