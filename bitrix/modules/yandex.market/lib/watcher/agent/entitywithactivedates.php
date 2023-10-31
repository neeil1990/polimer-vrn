<?php
namespace Yandex\Market\Watcher\Agent;

interface EntityWithActiveDates
{
	public function updateListener();

	public function getNextActiveDate();
}