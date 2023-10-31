<?php

namespace Yandex\Market\Utils;

use Bitrix\Main;
use Yandex\Market\Data\TextString;

class MessageRegistry
{
	private static $moduleInstance;

	private $classFinder;
	private $prefix;
	private $prefixes = [];
	private $included = [];

	public static function getModuleInstance()
	{
		if (static::$moduleInstance === null)
		{
			static::$moduleInstance = new static(
				ClassFinder::forModule()
			);
		}

		return static::$moduleInstance;
	}

	public function __construct(ClassFinder $classFinder, $prefix = '')
	{
		$this->classFinder = $classFinder;
		$this->prefix = $prefix;
	}

	public function load($className)
	{
		if (isset($this->included[$className])) { return; }

		$path = $this->classFinder->getPath($className);

		Main\Localization\Loc::loadMessages($path);
		$this->included[$className] = true;
	}

	public function getPrefix($className)
	{
		if (!isset($this->prefixes[$className]))
		{
			$this->prefixes[$className] = $this->makePrefix($className);
		}

		return $this->prefixes[$className];
	}
	
	private function makePrefix($className)
	{
		$relativeName = $this->classFinder->getRelativeName($className);
		$prefix = str_replace('\\', '_', $relativeName);
		$prefix = TextString::toUpper($prefix);

		return $this->prefix . $prefix;
	}
}