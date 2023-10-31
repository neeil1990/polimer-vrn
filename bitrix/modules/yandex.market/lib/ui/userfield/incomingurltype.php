<?php

namespace Yandex\Market\Ui\UserField;

use Yandex\Market;
use Bitrix\Main;

class IncomingUrlType extends StringType
{
	protected static function includeMessages()
	{
		Main\Localization\Loc::loadMessages(__FILE__);
		parent::includeMessages();
	}

	public static function GetAdminListViewHtml($userField, $htmlControl)
	{
		$value = (string)Helper\Value::asSingle($userField, $htmlControl);
		$value = trim($value);

		return $value !== ''
			? sprintf('<a href="%1$s" target="_blank">%1$s</a>', htmlspecialcharsbx($value))
			: '&nbsp;';
	}

	public static function GetEditFormHTML($userField, $htmlControl)
	{
		$result = parent::GetEditFormHTML($userField, $htmlControl);
		$result .= ' ' . static::getCheckButton($userField, $htmlControl);

		return $result;
	}

	protected static function getCheckButton($userField, $htmlControl)
	{
		$attributes = array_filter([
			'data-url' => static::getCheckUrl(),
			'data-site' => isset($userField['SETTINGS']['SITE_ID']) ? $userField['SETTINGS']['SITE_ID'] : null,
		]);
		$attributeString = Helper\Attributes::stringify($attributes);

		static::loadMessages();

		Market\Ui\Assets::loadPlugin('Ui.Input.IncomingUrlTest');
		Market\Ui\Assets::loadMessages([
			'UI_USER_FIELD_INCOMING_URL_MODAL_TITLE',
		]);

		return
			'<button class="adm-btn js-plugin-click" type="button" data-plugin="Ui.Input.IncomingUrlTest" ' . $attributeString . '>'
			. static::getLang('UI_USER_FIELD_INCOMING_URL_CHECK')
			. '</button>';
	}

	protected static function getCheckUrl()
	{
		return BX_ROOT . '/tools/' . Market\Config::getModuleName() . '/incomingurl/hellotest.php';
	}
}