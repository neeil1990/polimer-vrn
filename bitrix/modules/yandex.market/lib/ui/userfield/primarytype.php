<?php

namespace Yandex\Market\Ui\UserField;

use Yandex\Market;
use Bitrix\Main;

class PrimaryType extends StringType
{
	public static function getAdminListViewHtml($userField, $additionalParameters)
	{
		$value = (string)$additionalParameters['VALUE'];
		$result = '&mdash;';

		if ($value !== '')
		{
			$urlField = isset($userField['SETTINGS']['URL_FIELD'])
				? (string)$userField['SETTINGS']['URL_FIELD']
				: '';
			$url = '';

			if ($urlField !== '' && isset($userField['ROW'][$urlField]))
			{
				$url = (string)$userField['ROW'][$urlField];
			}

			if ($url !== '')
			{
				$result = sprintf(
					'<a href="%s">%s</a>',
					htmlspecialcharsbx($url),
					htmlspecialcharsbx($value)
				);
			}
			else
			{
				$result = htmlspecialcharsbx($value);
			}
		}

		return $result;
	}
}