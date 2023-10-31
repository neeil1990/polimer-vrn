<?php

namespace Yandex\Market\Ui\Plugin;

class Autocomplete extends TagInput
{
	public static function getJs()
	{
		return array_merge(parent::getJs(), [
			'Ui.Input.Autocomplete',
		]);
	}
}