<?php

namespace Yandex\Market\Ui\UserField\View;

use Bitrix\Main;
use Yandex\Market;

class Select extends EnumControl
{
	use Market\Reference\Concerns\HasLang;

	protected static function includeMessages()
	{
		Main\Localization\Loc::loadMessages(__FILE__);
	}

	public static function getControl($options, $value, array $attributes = [], array $settings = [])
	{
		$attributesString = Market\Ui\UserField\Helper\Attributes::stringify($attributes);

		$result = '<select ' . $attributesString . '>';

		if ($settings['ALLOW_NO_VALUE'] === 'Y')
		{
			$noValueCaption =
				(string)$settings['CAPTION_NO_VALUE'] !== ''
					? $settings['CAPTION_NO_VALUE']
					: static::getLang('USER_FIELD_VIEW_SELECT_NO_VALUE');

			$result .= '<option value="">' . htmlspecialcharsbx($noValueCaption) . '</option>';
		}

		$result .= static::getOptionsHtml($options, $value, $attributes, $settings);
		$result .= '</select>';

		return $result;
	}

	protected static function openGroup($title, $isFirst)
	{
		return sprintf('<optgroup label="%s">', htmlspecialcharsbx($title));
	}

	protected static function closeGroup()
	{
		return '</optgroup>';
	}

	/** @noinspection HtmlUnknownAttribute */
	protected static function option(array $option, array $attributes, $isSelected)
	{
		if (isset($option['ID']) || array_key_exists('ID', $option))
		{
			$result = sprintf(
				'<option value="%s" %s>%s</option>',
				htmlspecialcharsbx($option['ID']),
				$isSelected ? 'selected' : '',
				htmlspecialcharsbx($option['VALUE'])
			);
		}
		else
		{
			$result = sprintf(
				'<option %s>%s</option>',
				$isSelected ? 'selected' : '',
				htmlspecialcharsbx($option['VALUE'])
			);
		}

		return $result;
	}
}