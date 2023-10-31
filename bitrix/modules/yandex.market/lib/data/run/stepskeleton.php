<?php
namespace Yandex\Market\Data\Run;

abstract class StepSkeleton implements Step
{
	public function validateAction($action)
	{
		return true;
	}

	public function after($action)
	{
		// nothing by default
	}

	public function finalize($action)
	{
		// nothing by default
	}
}