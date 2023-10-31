<?php

namespace Yandex\Market\Ui\Plugin;

class TradingOutlet extends Autocomplete
{
	public static function getJs()
	{
		return array_merge(parent::getJs(), [
			'Ui.Input.TradingOutletFetcher',
			'Ui.Input.TradingOutlet',
		]);
	}
}