<?php

namespace Yandex\Market\Ui\UserField\Helper;

class Enum
{
	public static function toArray($enum)
	{
		if (is_array($enum))
		{
			$result = $enum;

			foreach ($result as &$option)
			{
				foreach ($option as $key => $value)
				{
					$option[$key] = htmlspecialcharsbx($value, ENT_COMPAT, false);
				}
			}
			unset($option);
		}
		else if ($enum instanceof \CDBResult)
		{
			$result = [];

			while ($option = $enum->GetNext())
			{
				$result[] = $option;
			}
		}
		else
		{
			$result = [];
		}

		return $result;
	}

	public static function unsetByMap($enum, $map, $field = 'ID', $invert = false)
	{
		foreach ($enum as $optionKey => $option)
		{
			$exists = isset($map[$option[$field]]);

			if ($exists !== $invert)
			{
				unset($enum[$optionKey]);
			}
		}

		return $enum;
	}
}