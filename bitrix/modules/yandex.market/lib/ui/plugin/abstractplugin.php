<?php

namespace Yandex\Market\Ui\Plugin;

use Yandex\Market\Ui;

abstract class AbstractPlugin
{
	public static function load()
	{
		static::loadMessages();
		static::loadCss();
		static::loadJs();
	}

	public static function loadCss()
	{
		Ui\Assets::loadPlugins(static::getCss(), 'css');
	}

	public static function getCss()
	{
		return [];
	}

	public static function loadMessages()
	{
		$messages = static::getMessages();

		if (empty($messages)) { return; }

		Ui\Assets::loadMessages($messages);
	}

	public static function getMessages()
	{
		return [];
	}

	public static function loadJs()
	{
		Ui\Assets::loadPlugins(static::getJs(), 'js');
	}

	public static function getJs()
	{
		return [];
	}
}