<?php
namespace Yandex\Market\Watcher\Agent;

use Yandex\Market\Data;
use Yandex\Market\Export;
use Yandex\Market\Environment;
use Yandex\Market\Logger;
use Yandex\Market\Config;
use Yandex\Market\Utils;

abstract class Processor
{
	protected static $environmentChecked = false;

	protected $method;
	protected $setupType;
	protected $setupId;
	/** @var array */
	protected $state;
	/** @var bool */
	protected $fromDb = false;
	protected $interceptor;

	public function __construct($method, $setupType, $setupId)
	{
		$this->method = $method;
		$this->setupType = $setupType;
		$this->setupId = $setupId;
	}

	public function run($action, array $parameters = [])
	{
		$state = null;

		try
		{
			$state = $this->state();

			$this->prepare();

			$process = $this->process($action, $parameters + [
				'step' => $state['STEP'],
				'stepOffset' => $state['OFFSET'],
				'initTime' => $state['START_TIME'],
				'timeLimit' => $this->timeLimit(),
			]);

			if ($process->isFinished())
			{
				$needRepeat = false;
				$this->releaseState($state);
			}
			else if (!$process->isSuccess())
			{
				$needRepeat = true;
			}
			else
			{
				$needRepeat = true;
				$this->saveState([
					'STEP' => $process->getStep(),
					'OFFSET' => $process->getStepOffset(),
					'START_TIME' => $state['START_TIME'],
				]);
			}

			$this->release();
		}
		catch (\Exception $exception)
		{
			$needRepeat = $this->processException($exception);

			$this->release();

			if ($needRepeat)
			{
				$this->makeLogger()->warning($exception);
			}
			else
			{
				$this->releaseState($state);
				$this->makeLogger()->error($exception);
			}
		}
		/** @noinspection PhpElementIsNotAvailableInCurrentPhpVersionInspection */
		catch (\Throwable $exception)
		{
			$needRepeat = $this->processException($exception);

			$this->release();

			if ($needRepeat)
			{
				$this->makeLogger()->warning($exception);
			}
			else
			{
				$this->releaseState($state);
				$this->makeLogger()->error($exception);
			}
		}

		return $needRepeat;
	}

	protected function prepare()
	{
		$this->checkEnvironment();

		$this->interceptor = new Export\Run\Diag\Interceptor(function($exception) {
			$this->processException($exception);
		});

		Environment::restore();
		$this->interceptor->bind();
	}

	protected function checkEnvironment()
	{
		if (static::$environmentChecked) { return; }

		static::$environmentChecked = true;

		$result = Environment::check();
		$logger = $this->makeLogger();

		foreach ($result->getErrors() as $error)
		{
			$logger->warning($error->getMessage());
		}
	}

	abstract protected function process($action, array $parameters);

	protected function release()
	{
		Environment::reset();

		if ($this->interceptor !== null)
		{
			$this->interceptor->unbind();
			$this->interceptor = null;
		}
	}

	protected function timeLimit()
	{
		if (Utils::isCli())
		{
			$option = 'export_run_agent_time_limit_cli';
			$default = 30;
		}
		else
		{
			$option = 'export_run_agent_time_limit';
			$default = 5;
		}

		return max(1, (int)Config::getOption($option, $default));
	}

	public function state()
	{
		if ($this->state === null)
		{
			$this->state = $this->loadState() ?: $this->createState();
		}

		return $this->state;
	}

	protected function loadState()
	{
		$result = null;

		$query = StateTable::getList([
			'filter' => [
				'=SETUP_TYPE' => $this->setupType,
				'=SETUP_ID' => $this->setupId,
				'=METHOD' => $this->method,
			]
		]);

		if ($row = $query->fetch())
		{
			$this->fromDb = true;

			if ((string)$row['STEP'] === '' || (int)$row['VERSION'] !== StateTable::VERSION)
			{
				$row['STEP'] = null;
				$row['OFFSET'] = null;
				$row['START_TIME'] = new Data\Type\CanonicalDateTime();
			}

			$result = $row;
		}

		return $result;
	}

	protected function createState()
	{
		return [
			'STEP' => null,
			'OFFSET' => null,
			'START_TIME' => new Data\Type\CanonicalDateTime(),
		];
	}

	protected function saveState(array $new)
	{
		$primary = [
			'SETUP_TYPE' => $this->setupType,
			'SETUP_ID' => $this->setupId,
			'METHOD' => $this->method,
		];
		$new['VERSION'] = StateTable::VERSION;

		if ($this->fromDb === false)
		{
			StateTable::add($primary + $new);
		}
		else
		{
			StateTable::update($primary, $new);
		}
	}

	protected function releaseState(array $state = null)
	{
		if ($state === null || !$this->fromDb) { return; }

		$expected = [
			'STEP' => '',
			'OFFSET' => '',
		];
		$diff = array_diff_assoc($expected, $state);

		if (empty($diff)) { return; }

		StateTable::update(
			[
				'SETUP_TYPE' => $this->setupType,
				'SETUP_ID' => $this->setupId,
				'METHOD' => $this->method,
			],
			$expected
		);
	}

	public function processException($exception)
	{
		return false;
	}

	/** @return Logger\Reference\Logger */
	abstract public function makeLogger();
}