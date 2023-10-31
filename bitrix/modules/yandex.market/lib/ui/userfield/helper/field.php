<?php

namespace Yandex\Market\Ui\UserField\Helper;

use Bitrix\Main;
use Yandex\Market;

class Field
{
	public static function extend($field, $name = null)
	{
		$field += [
			'MULTIPLE' => 'N',
			'EDIT_IN_LIST' => 'Y',
			'EDIT_FORM_LABEL' => $field['NAME'],
			'FIELD_NAME' => $name,
			'SETTINGS' => [],
		];

		if (!isset($field['USER_TYPE']) && isset($field['TYPE']))
		{
			$field['USER_TYPE'] = Market\Ui\UserField\Manager::getUserType($field['TYPE']);
		}

		return $field;
	}

	public static function extendValue($userField, $value, $row)
	{
		$defaults = [];

		if ($value !== null)
		{
			$defaults['VALUE'] = static::unifyValue($value);
		}

		if (isset($userField['VALUE']))
		{
			$userField['VALUE'] = static::unifyValue($userField['VALUE']);
		}

		if ($row !== null)
		{
			$defaults['ENTITY_VALUE_ID'] = isset($row['ID']) ? $row['ID'] : null;
			$defaults['ROW'] = $row;
		}

		return $userField + $defaults;
	}

	public static function unifyValue($value)
	{
		if (is_array($value))
		{
			foreach ($value as &$item)
			{
				if ($item instanceof Market\Data\Type\CanonicalDateTime)
				{
					$item = Main\Type\DateTime::createFromTimestamp($item->getTimestamp());
				}
			}
			unset($item);
		}
		else if ($value instanceof Market\Data\Type\CanonicalDateTime)
		{
			$value = Main\Type\DateTime::createFromTimestamp($value->getTimestamp());
		}

		return $value;
	}
}