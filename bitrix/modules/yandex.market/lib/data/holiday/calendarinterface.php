<?php

namespace Yandex\Market\Data\Holiday;

interface CalendarInterface
{
	public function title();

	public function holidays();

	public function workdays();
}