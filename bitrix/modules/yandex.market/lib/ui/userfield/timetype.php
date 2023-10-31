<?php

namespace Yandex\Market\Ui\UserField;

class TimeType extends StringType
{
	public static function GetEditFormHTML($userField, $htmlControl)
	{
		$size = !empty($userField['SETTINGS']['SIZE']) ? (int)$userField['SETTINGS']['SIZE'] : 5;

		if ($userField['ENTITY_VALUE_ID'] < 1 && (string)$userField['SETTINGS']['DEFAULT_VALUE'] !== '')
		{
			$htmlControl['VALUE'] = htmlspecialcharsbx($userField['SETTINGS']['DEFAULT_VALUE']);
		}

		return '<input class="adm-input" type="time" '.
			'name="'.$htmlControl['NAME'].'" '.
			'size="'.$size.'" '.
			($userField['SETTINGS']['MAX_LENGTH']>0 ? 'maxlength="'.$userField['SETTINGS']['MAX_LENGTH'].'" ': '').
			'value="'.$htmlControl['VALUE'].'" '.
			($userField['EDIT_IN_LIST'] !== 'Y' ? 'disabled="disabled" ': '').
			'>';
	}
}