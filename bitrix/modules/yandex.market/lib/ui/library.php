<?php

namespace Yandex\Market\Ui;

use Bitrix\Main;

class Library
{
	protected static $variantsMap = [
		'jquery' => [ 'jquery2', 'jquery' ],
	];
	protected static $varNameMap = [
		'jquery' => 'jQuery',
	];

	public static function loadConditional($name, $location = Main\Page\AssetLocation::AFTER_CSS)
	{
		$extension = static::getExtension($name);
		$varName = static::getVarName($name);

		Extension::loadConditional($extension, $varName, $location);
	}

	public static function load($name)
	{
		$extension = static::getExtension($name);

		Extension::load($extension);
	}

	protected static function getExtension($name)
	{
		if (!isset(static::$variantsMap[$name]))
		{
			throw new Main\ArgumentException(sprintf('not exists library %s', $name));
		}

		return Extension::getOne(static::$variantsMap[$name], true);
	}

	protected static function getVarName($name)
	{
		if (!isset(static::$varNameMap[$name]))
		{
			throw new Main\ArgumentException(sprintf('not exists varName for %s', $name));
		}

		return static::$varNameMap[$name];
	}
}