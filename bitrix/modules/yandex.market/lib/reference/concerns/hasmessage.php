<?php

namespace Yandex\Market\Reference\Concerns;

use Yandex\Market\Config;
use Yandex\Market\Utils\MessageRegistry;

trait HasMessage
{
	private static function getMessagePrefix()
	{
		return MessageRegistry::getModuleInstance()->getPrefix(self::class);
	}

	private static function includeSelfMessages()
	{
		MessageRegistry::getModuleInstance()->load(self::class);
	}

	protected static function getMessage($code, $replaces = null, $fallback = null)
	{
		self::includeSelfMessages();

		$fullCode = self::getMessagePrefix() . '_' . $code;

		if ($fallback === null) { $fallback = $code; }

		return Config::getLang($fullCode, $replaces, $fallback);
	}
}