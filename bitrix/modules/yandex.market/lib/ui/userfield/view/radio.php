<?php

namespace Yandex\Market\Ui\UserField\View;

use Yandex\Market;

class Radio extends EnumControl
{
	public static function getControl($options, $value, array $attributes = [], array $settings = [])
	{
		return static::getOptionsHtml($options, $value, $attributes, $settings);
	}

	protected static function openGroup($title, $isFirst)
	{
		return sprintf(
			'<strong style="display: block; margin: %s 0 5px">%s</strong>',
			$isFirst ? '0' : '10px',
			htmlspecialcharsbx($title)
		);
	}

	protected static function closeGroup()
	{
		return '';
	}

	protected static function option(array $option, array $attributes, $isSelected)
	{
		$attributesString = Market\Ui\UserField\Helper\Attributes::stringify($attributes);
		$id = htmlspecialcharsbx($option['ID']);
		$value = htmlspecialcharsbx($option['VALUE']);

		if ($isSelected)
		{
			$attributesString .= ' checked';
		}

		return <<<EOL
			<label>
				<input type="radio" value="{$id}" {$attributesString} />
				$value
			</label>
			<br>
EOL;
	}
}