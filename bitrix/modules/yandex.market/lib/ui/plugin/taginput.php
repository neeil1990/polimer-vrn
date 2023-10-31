<?php

namespace Yandex\Market\Ui\Plugin;

use Bitrix\Main\Localization\Loc;

class TagInput extends AbstractPlugin
{
	public static function getCss()
	{
		return [
			'/js/lib.select2.select2',
			'/js/lib.select2.select2\\.theme',
		];
	}
	
	public static function getJs()
	{
		return [
			'lib.select2.select2',
			'Ui.Input.TagInput',
		];
	}
	
	public static function getMessages()
	{
		Loc::loadMessages(__FILE__);
		
		return [
			'CHOSEN_PLACEHOLDER',
			'CHOSEN_NO_RESULTS',
			'CHOSEN_LOAD_PROGRESS',
			'CHOSEN_LOAD_ERROR',
			'CHOSEN_SEARCHING',
			'CHOSEN_TOO_LONG',
			'CHOSEN_TOO_SHORT',
			'CHOSEN_MAX_SELECT',
		];
	}
}