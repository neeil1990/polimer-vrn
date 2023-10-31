<?php

namespace Yandex\Market\Trading\Service\Reference\Action;

abstract class ComplexActivity extends AbstractActivity
{
	/** @return array<string, AbstractActivity> */
	abstract public function getActivities();

	public function onlyContents()
	{
		return false;
	}

	protected function makeCommand($title, array $payload, array $parameters = [])
	{
		return new CommandActivity($this->provider, $this->environment, $title, $payload, $parameters + [
			'USE_GROUP' => $this->useGroup(),
		]);
	}
}