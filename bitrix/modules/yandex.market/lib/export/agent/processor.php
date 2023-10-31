<?php
namespace Yandex\Market\Export\Agent;

use Yandex\Market\Glossary;
use Yandex\Market\Logger;
use Yandex\Market\Result;
use Yandex\Market\Watcher;
use Yandex\Market\Export\Setup;
use Yandex\Market\Export\Run;
use Yandex\Market\Ui;

class Processor extends Watcher\Agent\Processor
{
	public function __construct($method, $setupId)
	{
		parent::__construct($method, Glossary::SERVICE_EXPORT, $setupId);
	}

	protected function process($action, array $parameters)
	{
		$setup = Setup\Model::loadById($this->setupId);

		if ($action === Run\Processor::ACTION_CHANGE && !$setup->isFileReady())
		{
			return new Result\StepProcessor();
		}

		if ($action === Run\Processor::ACTION_FULL)
		{
			$parameters['usePublic'] = false;
		}

		$processor = new Run\Processor($setup, $parameters);

		return $processor->run($action);
	}

	public function makeLogger()
	{
		$logger = new Logger\Logger();
		$logger->allowCheckExists();
		$logger->resetContext([
			'ENTITY_TYPE' => Logger\Table::ENTITY_TYPE_EXPORT_AGENT,
			'ENTITY_PARENT' => $this->setupId,
			'ENTITY_ID' => $this->method,
		]);

		return $logger;
	}

	public function processException($exception)
	{
		Ui\Checker\Notify::error();

		return false;
	}
}