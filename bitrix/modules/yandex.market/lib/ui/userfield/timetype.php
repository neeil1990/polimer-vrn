<?php

namespace Yandex\Market\Ui\UserField;

class TimeType extends StringType
{
	public static function GetEditFormHTML($userField, $htmlControl)
	{
		if ($userField['ENTITY_VALUE_ID'] < 1 && (string)$userField['SETTINGS']['DEFAULT_VALUE'] !== '')
		{
			$htmlControl['VALUE'] = htmlspecialcharsbx($userField['SETTINGS']['DEFAULT_VALUE']);
		}

		return '<input class="adm-input" type="time" '.
			'name="'.$htmlControl['NAME'].'" '.
			'size="'.$userField['SETTINGS']['SIZE'].'" '.
			($userField['SETTINGS']['MAX_LENGTH']>0 ? 'maxlength="'.$userField['SETTINGS']['MAX_LENGTH'].'" ': '').
			'value="'.$htmlControl['VALUE'].'" '.
			($userField['EDIT_IN_LIST'] !== 'Y' ? 'disabled="disabled" ': '').
			'>';
	}
}