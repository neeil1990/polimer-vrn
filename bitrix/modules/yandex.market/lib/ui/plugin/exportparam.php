<?php

namespace Yandex\Market\Ui\Plugin;

class ExportParam extends Autocomplete
{
	public static function getJs()
	{
		return array_merge(parent::getJs(), [
			'Ui.Input.AutocompleteFetcher',
			'Ui.Input.ExportParam',
		]);
	}
}