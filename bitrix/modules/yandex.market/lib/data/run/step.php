<?php
namespace Yandex\Market\Data\Run;

use Yandex\Market\Result;

interface Step
{
	public function getName();

	/** @return bool */
	public function validateAction($action);

	/** @return Result\Step */
	public function run($action, $offset = null);

	public function after($action);

	public function finalize($action);
}