<?php

namespace Yandex\Market\Ui\UserField\Fieldset;

use Yandex\Market\Data\TextString;

class Helper
{
	public static function makeChildAttributes($userField, $type = 'row__child')
	{
		$attributes = [];

		if (static::hasParentFieldset($userField))
		{
			$attributes['class'] = static::getParentFieldsetName($userField, $type);
			$attributes['data-name'] = static::makeRelativeName($userField, $userField['FIELD_NAME']);
		}

		return $attributes;
	}

	public static function hasParentFieldset($userField)
	{
		return !empty($userField['SETTINGS']['PARENT_FIELDSET_BASE']);
	}

	public static function getParentFieldsetName($userField, $type)
	{
		$parentName = $userField['SETTINGS']['PARENT_FIELDSET_BASE'];

		return $parentName . '-' . $type;
	}

	public static function makeRelativeName($userField, $inputName)
	{
		if (empty($userField['SETTINGS']['PARENT_FIELDSET_NAME'])) { return $inputName; }

		$parentName = $userField['SETTINGS']['PARENT_FIELDSET_NAME'];
		$parentName = preg_replace('/\[]$/', '', $parentName);

		if (TextString::getPosition($inputName, $parentName) === 0)
		{
			$result = TextString::getSubstring(
				$inputName,
				TextString::getLength($parentName)
			);
			$result = preg_replace('/^\[\d+]/', '', $result); // remove collection index

			if (preg_match('/^\[([^]]+)]$/', $result, $simplifyMatches)) // remove root quotes
			{
				$result = $simplifyMatches[1];
			}
		}
		else
		{
			$result = $inputName;
		}

		return $result;
	}
}