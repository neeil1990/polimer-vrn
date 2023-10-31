<?php

namespace Yandex\Market\Utils\ServerStamp;

interface PropertyInterface
{
	public function name();

	public function title();

	public function reset();

	public function collect();

	public function test($stored, $current);
}