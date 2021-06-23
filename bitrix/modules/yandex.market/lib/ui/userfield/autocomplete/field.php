<?php

namespace Yandex\Market\Ui\UserField\Autocomplete;

use Yandex\Market;

abstract class Field
{
	abstract public static function GetUserTypeDescription();

	abstract public static function GetDBColumnType($userField);

	/**
	 * @return \Yandex\Market\Ui\UserField\Autocomplete\Provider
	 */
	abstract public static function getDataProvider();

	/**
	 * @return string
	 */
	abstract public static function getLangKey();

	public static function PrepareSettings($userField)
	{
		return [];
	}

	public static function GetSettingsHTML($userField, $htmlControl, $isVarsFromForm)
	{
		return '';
	}

	public static function GetEditFormHTML($userField, $htmlControl)
	{
		global $APPLICATION;

		$controlId = preg_replace('/[^a-zA-Z0-9_]/i', 'x', $htmlControl['NAME'] . '_' . mt_rand(0, 10000));
		$autoCompleteValue = null;
		$dataProvider = static::getDataProvider();
		$langKey = static::getLangKey();
		$isMultiple = false;
		$isGridMode = (isset($htmlControl['MODE']) && $htmlControl['MODE'] === 'list');

		if (isset($userField['MULTIPLE']) && $userField['MULTIPLE'] === 'Y')
		{
			$isMultiple = true;
			$autoCompleteValueList = $dataProvider::getValueForAutoCompleteMulti($userField, $htmlControl['VALUE']);
			$autoCompleteValue = implode(PHP_EOL, $autoCompleteValueList);
		}
		else
		{
			$autoCompleteValue = $dataProvider::getValueForAutoComplete($userField, $htmlControl['VALUE']);
		}

		ob_start();

		$APPLICATION->IncludeComponent(
			'bitrix:main.lookup.input',
			'ym_userfield',
			[
				'CONTROL_ID' => $controlId,
				'INPUT_NAME' => $htmlControl['NAME'],
				'INPUT_NAME_STRING' => 'inp_'.$htmlControl['NAME'],
				'INPUT_VALUE_STRING' => $autoCompleteValue,
				'START_TEXT' => $isGridMode ? '' : Market\Config::getLang($langKey . 'INPUT_INVITE'),
				'MULTIPLE' => ($isMultiple ? 'Y' : 'N'),
				'FILTER' => 'Y',
				'PROVIDER' => $dataProvider,
				'SIBLINGS_INIT' => ($isGridMode ? 'Y' : 'N'),
			],
			null,
			[ 'HIDE_ICONS' => 'Y' ]
		);

		return ob_get_clean();
	}

	public static function GetEditFormHTMLMulty($userField, $htmlControl)
	{
		$userField['MULTIPLE'] = 'Y';

		return static::GetEditFormHTML($userField, $htmlControl);
	}

	public static function GetFilterHTML($userField, $htmlControl)
	{
		global $APPLICATION;

		$dataProvider = static::getDataProvider();
		$isMainUiFilter = (isset($htmlControl['FORM_NAME']) && $htmlControl['FORM_NAME'] == 'main-ui-filter');
		$inputName = $htmlControl['NAME'] . ($isMainUiFilter ? '' : '[]');
		$autoCompleteValueList = $dataProvider::GetValueForAutoCompleteMulti($userField, $htmlControl['VALUE']);
		$autoCompleteValue = implode(PHP_EOL, $autoCompleteValueList);

		ob_start();

		$control_id = $APPLICATION->IncludeComponent(
			'bitrix:main.lookup.input',
			'ym_userfield',
			[
				'INPUT_NAME' => $inputName,
				'INPUT_NAME_STRING' => 'inp_' . $inputName,
				'INPUT_VALUE_STRING' => $autoCompleteValue,
				'MULTIPLE' => $isMainUiFilter ? 'N' : 'Y',
				'MAX_WIDTH' => '200',
				'MIN_HEIGHT' => '24',
				'MAIN_UI_FILTER' => ($isMainUiFilter ? 'Y' : 'N'),
				'FILTER' => 'Y',
				'PROVIDER' => $dataProvider
			],
			null,
			[ 'HIDE_ICONS' => 'Y' ]
		);

		?>
		<script type="text/javascript">
			var arClearHiddenFields = arClearHiddenFields;
			if (!!arClearHiddenFields) {
				indClearHiddenFields = arClearHiddenFields.length;
				arClearHiddenFields[indClearHiddenFields] = 'jsMLI_<?= $control_id; ?>';
			}
		</script>
		<?

		return ob_get_clean();
	}

	public static function GetFilterData($userField, $htmlControl)
	{
		$dataProvider = static::getDataProvider();
		$dataList = $dataProvider::getList();
		$enum = [];

		foreach ($dataList as $row)
		{
			$enum[$row['ID']] = (str_repeat('.', $row['DEPTH_LEVEL'])) . $row['NAME'];
		}

		return [
			'id' => $htmlControl['ID'],
			'name' => $htmlControl['NAME'],
			'type' => 'list',
			'items' => $enum,
			'params' => array('multiple' => 'Y'),
			'filterable' => ''
		];
	}

	public static function GetAdminListViewHTML($userField, $htmlControl)
	{
		$dataProvider = static::getDataProvider();
		$autoCompleteValueList = $dataProvider::getValueForAutoCompleteMulti($userField, $htmlControl['VALUE']);

		return implode(' / ', $autoCompleteValueList);
	}

	public static function GetAdminListEditHtmlMulty($userField, $htmlControl)
	{
		$userField['MULTIPLE'] = 'Y';
		$htmlControl['MODE'] = 'list';

		return static::GetEditFormHTML($userField, $htmlControl);
	}

	public static function GetAdminListEditHtml($userField, $htmlControl)
	{
		$htmlControl['MODE'] = 'list';

		return static::GetEditFormHTML($userField, $htmlControl);
	}
}