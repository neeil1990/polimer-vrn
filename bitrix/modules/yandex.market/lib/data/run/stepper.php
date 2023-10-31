<?php
namespace Yandex\Market\Data\Run;

use Yandex\Market\Result;

class Stepper
{
	protected $steps;
	protected $resourceLimit;

	/** @param Step[] $steps */
	public function __construct(array $steps, ResourceLimit $resourceLimit = null)
	{
		$this->steps = $steps;
		$this->resourceLimit = $resourceLimit;
	}

	public function process($action, $interruptStep = null, $interruptOffset = null)
	{
		$result = new Result\StepProcessor();
		$started = false;
		$interrupted = false;

		$result->setTotal(count($this->steps));

		foreach ($this->steps as $step)
		{
			$name = $step->getName();

			if ($interruptStep === null || $interruptStep === '')
			{
				$justStarted = true;
				$started = true;
				$stepOffset = null;
			}
			else if ($interruptStep === $name)
			{
				$justStarted = true;
				$started = true;
				$stepOffset = $interruptOffset !== '' ? $interruptOffset : null;
			}
			else if (!$started)
			{
				$result->increaseProgress(1);
				continue;
			}
			else
			{
				$justStarted = false;
				$stepOffset = null;
			}

			if (!$justStarted && $this->resourceLimit !== null && $this->resourceLimit->isExpired())
			{
				$interrupted = true;

				$result->setStep($name);
				$result->setStepOffset($stepOffset);
				break;
			}

			if (!$step->validateAction($action))
			{
				$result->increaseProgress(1);
				continue;
			}

			$stepResult = $step->run($action, $stepOffset);

			if (!$stepResult->isFinished())
			{
				$interrupted = true;

				$result->setStep($name);
				$result->setStepOffset($stepResult->getOffset());
				$result->increaseProgress($stepResult->getProgressRatio());
				$result->setStepReadyCount($stepResult->getReadyCount());

				break;
			}

			$step->after($action);

			$result->increaseProgress(1);
		}

		if (!$interrupted)
		{
			$this->finalize($action);
		}

		return $result;
	}

	private function finalize($action)
	{
		foreach ($this->steps as $step)
		{
			if (!$step->validateAction($action)) { continue; }

			$step->finalize($action);
		}
	}
}