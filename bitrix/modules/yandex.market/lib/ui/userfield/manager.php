<?php

namespace Yandex\Market\Ui\UserField;

use Yandex\Market;
use Bitrix\Main;

class Manager
{
	public static function getUserType($type)
	{
		global $USER_FIELD_MANAGER;

		$localTypeClassName = static::getLocalTypeClassName($type);

		if (!class_exists($localTypeClassName))
		{
			$result = $USER_FIELD_MANAGER->GetUserType($type);
		}
		else if (method_exists($localTypeClassName, 'GetUserTypeDescription'))
		{
			$result = $localTypeClassName::GetUserTypeDescription();
			$result['CLASS_NAME'] = $localTypeClassName;
		}
		else
		{
			$result = [
				'CLASS_NAME' => $localTypeClassName,
			];
		}

		return $result;
	}

	public static function getLocalTypeClassName($type)
	{
		return __NAMESPACE__ . '\\' . ucfirst($type) . 'Type';
	}
}