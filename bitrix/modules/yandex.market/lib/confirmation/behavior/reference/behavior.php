<?php

namespace Yandex\Market\Confirmation\Behavior\Reference;

use Bitrix\Main;
use Yandex\Market;

abstract class Behavior
{
	abstract public function validate($contents);

	abstract public function formatDisplay($domain, $contents);

	abstract public function install($domain, $contents);

	abstract public function uninstall($domain, $contents);
}