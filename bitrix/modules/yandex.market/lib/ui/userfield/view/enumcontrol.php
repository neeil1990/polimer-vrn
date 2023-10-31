<?php

namespace Yandex\Market\Ui\UserField\View;

use Bitrix\Main;
use Yandex\Market;

abstract class EnumControl
{
	protected static function getOptionsHtml($options, $value, array $attributes, array $settings)
	{
		$useDefaultValue = ($value === null);
		$valueMap = static::getValueMap($value);
		$activeGroup = null;
		$defaultGroup = !empty($settings['DEFAULT_GROUP']) ? $settings['DEFAULT_GROUP'] : null;
		$foundSelected = false;
		$result = '';

		foreach (Market\Ui\UserField\Helper\Enum::toArray($options) as $option)
		{
			$isSelected = $useDefaultValue ? $option['DEF'] === 'Y' : isset($valueMap[$option['ID']]);
			$optionGroup = isset($option['GROUP']) && $option['GROUP'] !== '' ? $option['GROUP'] : $defaultGroup;

			if ($optionGroup !== $activeGroup)
			{
				if ($activeGroup !== null)
				{
					$result .= static::closeGroup();
				}

				if ($optionGroup !== null)
				{
					$result .= static::openGroup($optionGroup, $activeGroup === null);
				}

				$activeGroup = $optionGroup;
			}

			if ($isSelected) { $foundSelected = true; }

			$result .= static::option($option, $attributes, $isSelected);
		}

		if (!$foundSelected && $settings['ALLOW_UNKNOWN'] === 'Y')
		{
			$values = is_array($value) ? $value : [ $value ];

			foreach ($values as $oneValue)
			{
				if (Market\Utils\Value::isEmpty($oneValue)) { continue; }

				$result .= static::option([ 'VALUE' => $oneValue ], $attributes, true);
			}
		}

		if ($activeGroup !== null) { $result .= static::closeGroup(); }

		return $result;
	}

	protected static function openGroup($title, $isFirst)
	{
		throw new Main\NotImplementedException();
	}

	protected static function option(array $option, array $attributes, $isSelected)
	{
		throw new Main\NotImplementedException();
	}

	protected static function closeGroup()
	{
		throw new Main\NotImplementedException();
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