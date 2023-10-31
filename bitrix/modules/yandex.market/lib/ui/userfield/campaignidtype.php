<?php

namespace Yandex\Market\Ui\UserField;

use Yandex\Market;
use Bitrix\Main;

class CampaignIdType extends StringType
{
	public static function SanitizeFields($userField, $value)
	{
		$valueTrimmed = trim($value);

		if (preg_match('/^\d{1,3}-(\d+)$/', $valueTrimmed, $matches))
		{
			$result = $matches[1];
		}
		else
		{
			$result = $value;
		}

		return $result;
	}
}