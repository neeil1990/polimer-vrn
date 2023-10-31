<?php
namespace Yandex\Market\Watcher\Agent;

interface EntityRefreshable
{
	public function hasFullRefresh();

	public function getRefreshPeriod();

	public function hasRefreshTime();

	public function getRefreshTime();

	public function getRefreshNextExec();
}