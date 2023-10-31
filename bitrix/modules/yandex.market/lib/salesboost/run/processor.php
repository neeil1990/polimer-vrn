<?php
namespace Yandex\Market\SalesBoost\Run;

use Bitrix\Main;
use Yandex\Market\Data;
use Yandex\Market\SalesBoost;

class Processor implements Data\Run\Processor
{
	protected $setup;
	protected $parameters;
	/** @var Data\Run\Step[] */
	protected $steps;
	protected $limitResource;

	public function __construct(array $parameters = [])
	{
		$this->parameters = $this->extendParameters($parameters);
		$this->steps = [
			new Steps\Collector($this),
			new Steps\Planner($this),
			new Steps\Submitter($this),
		];
		$this->limitResource = new Data\Run\ResourceLimit([
			'startTime' => $this->parameter('startTime'),
			'timeLimit' => $this->parameter('timeLimit')
		]);
	}

	protected function extendParameters($parameters)
	{
		if (isset($parameters['initTime']) && $parameters['initTime'] instanceof Main\Type\DateTime)
		{
			$canonicalTime = Data\DateTime::toCanonical($parameters['initTime']);
			$canonicalTime->setDefaultTimeZone();

			$parameters['initTimeUTC'] = $canonicalTime;
		}

		return $parameters;
	}

	public function steps()
	{
		return $this->steps;
	}

	public function run($action = self::ACTION_FULL)
	{
		$this->loadModules();

		return $this->runStepper($action);
	}

	protected function loadModules()
	{
		if (!Main\Loader::includeModule('iblock'))
		{
			throw new Main\SystemException('cant load iblock module');
		}
	}

	protected function runStepper($action)
	{
		$stepper = new Data\Run\Stepper($this->steps);

		return $stepper->process($action, $this->parameter('step'), $this->parameter('stepOffset'));
	}

	public function parameter($name, $default = null)
	{
		return isset($this->parameters[$name]) ? $this->parameters[$name] : $default;
	}

	public function isExpired()
	{
		$this->limitResource->tick();

		return $this->limitResource->isExpired();
	}
}