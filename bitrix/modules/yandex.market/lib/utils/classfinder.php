<?php

namespace Yandex\Market\Utils;

use Bitrix\Main;
use Yandex\Market;

class ClassFinder
{
	private $namespace;
	private $namespaceLength;
	private $path;

	public static function forModule()
	{
		return new static(
			Market\Config::getNamespace(),
			Market\Config::getModulePath()
		);
	}

	public function __construct($namespace, $path)
	{
		$this->namespace = ltrim($namespace, '\\');
		$this->path = $path;

		$this->namespaceLength = Market\Data\TextString::getLength($this->namespace);
	}

	public function getPath($className)
	{
		$relativeClassName =  $this->getRelativeName($className);
		$relativePath = str_replace('\\', Main\IO\Path::DIRECTORY_SEPARATOR, $relativeClassName);
		$relativePath = Market\Data\TextString::toLower($relativePath) . '.php';

		return
			$this->path
			. Main\IO\Path::DIRECTORY_SEPARATOR
			. $relativePath;
	}

	public function getRelativeName($className)
	{
		if (Market\Data\TextString::getPositionCaseInsensitive($className, $this->namespace) !== 0)
		{
			throw new Main\NotSupportedException(sprintf(
				'class %s outside %s not supported',
				$className,
				$this->namespace
			));
		}

		return Market\Data\TextString::getSubstring($className, $this->namespaceLength + 1);
	}
}