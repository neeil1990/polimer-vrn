<?php

namespace Yandex\Market\Ui\UserField;

use Yandex\Market;
use Bitrix\Main;

class StringType
{
	use Market\Reference\Concerns\HasLang;
	use Concerns\HasCompatibleExtends;

	public static function getCommonExtends()
	{
		return Main\UserField\Types\StringType::class;
	}

	public static function getCompatibleExtends()
	{
		return \CUserTypeString::class;
	}

	protected static function includeMessages()
	{
		Main\Localization\Loc::loadMessages(__FILE__);
	}

	public static function getUserTypeDescription()
	{
		return array_diff_key(static::callParent('getUserTypeDescription'), [
			'USE_FIELD_COMPONENT' => true,
		]);
	}

	public static function CheckFields($arUserField, $value)
	{
		return static::callParent('CheckFields', [$arUserField, $value]);
	}

	public static function GetFilterHTML($userField, $htmlControl)
	{
		return static::callParent('GetFilterHTML', [$userField, $htmlControl]);
	}

	public static function GetFilterData($arUserField, $arHtmlControl)
	{
		return static::callParent('GetFilterData', [$arUserField, $arHtmlControl]);
	}

	public static function GetEditFormHtmlMulty($userField, $htmlControl)
	{
		$values = Helper\Value::asMultiple($userField, $htmlControl);
		$values = static::sanitizeMultipleValues($values);
		$attributes = Fieldset\Helper::makeChildAttributes($userField);
		$renderer = function($name, $value) use ($userField) {
			return static::GetEditFormHTML(array_diff_key($userField, [ 'VALUE' => true ]), [
				'NAME' => $name,
				'VALUE' => $value,
			]);
		};

		return View\Collection::render($userField['FIELD_NAME'], $values, $renderer, $attributes);
	}

	public static function GetEditFormHTML($userField, $htmlControl)
	{
		$attributes = Helper\Attributes::extractFromSettings($userField['SETTINGS']);

		$result = static::getEditInput($userField, $htmlControl);
		$result = Helper\Attributes::insert($result, $attributes);

		if (isset($userField['SETTINGS']['COPY_BUTTON']))
		{
			$result .= ' ' . static::getCopyButton($userField, $htmlControl);
		}

		return $result;
	}

	public static function GetAdminListViewHtml($userField, $htmlControl)
	{
		$value = (string)Helper\Value::asSingle($userField, $htmlControl);

		return $value !== '' ? $value : '&nbsp;';
	}

	protected static function sanitizeMultipleValues(array $values)
	{
		$result = [];

		foreach ($values as $value)
		{
			if (is_scalar($value) && (string)$value !== '')
			{
				$result[] = htmlspecialcharsbx($value);
			}
		}

		return $result;
	}

	protected static function getEditInput($userField, $htmlControl)
	{
		$value = Helper\Value::asSingle($userField, $htmlControl);

		if ($userField['SETTINGS']['ROWS'] < 2)
		{
			$htmlControl['VALIGN'] = 'middle';
			$attributes = [
				'type' => 'text',
				'name' => $htmlControl['NAME'],
			];
			$attributes += array_filter([
				'size' => isset($userField['SETTINGS']['SIZE']) ? (int)$userField['SETTINGS']['SIZE'] : null,
				'maxlength' => isset($userField['SETTINGS']['MAX_LENGTH']) ? (int)$userField['SETTINGS']['MAX_LENGTH'] : null,
				'disabled' => $userField['EDIT_IN_LIST'] !== 'Y',
				'data-multiple' => $userField['MULTIPLE'] !== 'N',
			]);
			
			return sprintf(
				'<input %s value="%s" />',
				Helper\Attributes::stringify($attributes),
				htmlspecialcharsbx($value)
			);
		}

		$attributes = [
			'name' => $htmlControl['NAME'],
		];
		$attributes += array_filter([
			'cols' => isset($userField['SETTINGS']['SIZE']) ? (int)$userField['SETTINGS']['SIZE'] : null,
			'rows' => isset($userField['SETTINGS']['ROWS']) ? (int)$userField['SETTINGS']['ROWS'] : null,
			'maxlength' => isset($userField['SETTINGS']['MAX_LENGTH']) ? (int)$userField['SETTINGS']['MAX_LENGTH'] : null,
			'disabled' => $userField['EDIT_IN_LIST'] !== 'Y',
			'data-multiple' => $userField['MULTIPLE'] !== 'N',
		]);

		return sprintf(
			'<textarea %s>%s</textarea>',
			Helper\Attributes::stringify($attributes),
			htmlspecialcharsbx($value)
		);
	}

	protected static function getCopyButton($userField, $htmlControl)
	{
		static::loadMessages();

		Market\Ui\Assets::loadPlugin('Ui.Input.CopyClipboard');
		Market\Ui\Assets::loadMessages([
			'INPUT_COPY_CLIPBOARD_SUCCESS',
			'INPUT_COPY_CLIPBOARD_FAIL',
		]);

		return
			'<button class="adm-btn js-plugin-click" type="button" data-plugin="Ui.Input.CopyClipboard">'
				. static::getLang('UI_USER_FIELD_STRING_COPY')
			. '</button>';
	}
}