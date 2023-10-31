<?php

namespace Yandex\Market\Ui\UserField\View;

use Yandex\Market;

class Number
{
	public static function getControl($value, array $attributes = [])
	{
		$defaultAttributes = [
			'type' => 'text',
			'size' => 2,
			'inputmode' => 'numeric',
		];

		if ((string)$value !== '')
		{
			$defaultAttributes['value'] = $value;
		}

		$allAttributes = $attributes + $defaultAttributes;

		return '<input ' . Market\Ui\UserField\Helper\Attributes::stringify($allAttributes) . ' />';
	}
}