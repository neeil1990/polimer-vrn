<?php

namespace Yandex\Market\Ui\UserField\View;

use Bitrix\Main;
use Yandex\Market;

class Select
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

		$result .= static::getOptionsHtml($options, $value, $settings);
		$result .= '</select>';

		return $result;
	}

	protected static function getOptionsHtml($options, $value, $settings)
	{
		$useDefaultValue = ($value === null);
		$valueMap = static::getValueMap($value);
		$activeGroup = null;
		$defaultGroup = !empty($settings['DEFAULT_GROUP']) ? $settings['DEFAULT_GROUP'] : null;
		$result = '';

		foreach (Market\Ui\UserField\Helper\Enum::toArray($options) as $option)
		{
			$isSelected = $useDefaultValue ? $option['DEF'] === 'Y' : isset($valueMap[$option['ID']]);
			$optionGroup = isset($option['GROUP']) ? $option['GROUP'] : $defaultGroup;

			if ($optionGroup !== $activeGroup)
			{
				if ($activeGroup !== null)
				{
					$result .= '</optgroup>';
				}

				$activeGroup = $optionGroup;

				if ($optionGroup !== null)
				{
					$result .= '<optgroup label="' . str_replace('"', '\\"', $optionGroup) . '">';
				}
			}

			$result .=
				'<option value="' . $option['ID'] . '" ' . ($isSelected ? 'selected' : '') . '>'
				. $option['VALUE']
				. '</option>';
		}

		if ($activeGroup !== null) { $result .= '</optgroup>'; }

		return $result;
	}

	protected static function getValueMap($value)
	{
		if (is_array($value))
		{
			$result = array_flip($value);
		}
		else if ((string)$value !== '')
		{
			$result = [ $value => true ];
		}
		else
		{
			$result = [];
		}

		return $result;
	}
}