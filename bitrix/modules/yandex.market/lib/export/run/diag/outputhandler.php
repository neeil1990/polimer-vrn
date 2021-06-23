<?php

namespace Yandex\Market\Export\Run\Diag;

use Bitrix\Main;

if (!CheckVersion(Main\ModuleManager::getVersion('main'), '16.0.0')) { return; }

class OutputHandler implements Main\Diag\IExceptionHandlerOutput
{
	protected $callback;
	protected $decorated;

	public function __construct(\Closure $callback, Main\Diag\IExceptionHandlerOutput $decorated = null)
	{
		$this->callback = $callback;
		$this->decorated = $decorated;
	}

	public function renderExceptionMessage($exception, $debug = false)
	{
		call_user_func($this->callback, $exception);

		if ($this->decorated)
		{
			$this->decorated->renderExceptionMessage($exception, $debug);
		}
	}
}