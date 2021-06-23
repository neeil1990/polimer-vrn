<?php

namespace Yandex\Market\Ui\UserField;

use Yandex\Market;
use Bitrix\Main;

Main\Localization\Loc::loadMessages(__FILE__);

class BuyerProfileType extends EnumerationType
{
	protected static $variants = [];

	public static function GetList($arUserField)
	{
		$userId = static::extractUserFieldUserId($arUserField);
		$personType = static::extractUserFieldPersonType($arUserField);
		$variants = static::getVariants($userId, $personType);

		$result = new \CDBResult();
		$result->InitFromArray($variants);

		return $result;
	}

	public static function GetEditFormHTML($arUserField, $arHtmlControl)
	{
		$result = parent::GetEditFormHTML($arUserField, $arHtmlControl);
		$result .= ' ';
		$result .= static::getEditButton($arUserField, $arHtmlControl['NAME']);

		static::loadLangMessages();

		return $result;
	}

	protected static function getEditButton($userField, $name)
	{
		$htmlId = Market\Ui\UserField\Helper\Attributes::convertNameToId($name) . '_REFRESH';

		return
			static::getEditButtonHtml($htmlId)
			. static::getEditButtonScript($userField, $htmlId);
	}

	protected static function getEditButtonHtml($htmlId)
	{
		return '<input type="button" value="' . htmlspecialcharsbx(Market\Config::getLang('USER_FIELD_BUYER_PROFILE_EDIT')) . '" id="' . htmlspecialcharsbx($htmlId) . '" />';
	}

	protected static function getEditButtonScript($userField, $htmlId)
	{
		$userId = static::extractUserFieldUserId($userField);
		$personTypeValue = static::extractUserFieldPersonType($userField);
		$personTypeField = static::getUserFieldPersonTypeField($userField);

		Market\Ui\Assets::loadPlugin('Ui.Input.ProfileEdit');

		return Market\Ui\Assets::initPlugin('Ui.Input.ProfileEdit', '#' . $htmlId, [
			'editUrl' => static::getEditUrl($userField),
			'refreshUrl' => static::getRefreshUrl(),
			'personTypeId' => $personTypeValue,
			'personTypeElement' => 'select[name="' . $personTypeField . '"]',
			'userId' => $userId,
		]);
	}

	protected static function loadLangMessages()
	{
		Market\Ui\Assets::loadMessages([
			'USER_FIELD_BUYER_PROFILE_REFRESH_FAIL',
		]);
	}

	protected static function getRefreshUrl()
	{
		return BX_ROOT . '/tools/' . Market\Config::getModuleName() . '/buyerprofile/enum.php';
	}

	protected static function getEditUrl($userField)
	{
		$queryParameters = [
			'lang' => LANGUAGE_ID,
		];

		if (isset($userField['SETTINGS']['SERVICE']))
		{
			$queryParameters['service'] = $userField['SETTINGS']['SERVICE'];
		}

		return Market\Ui\Admin\Path::getModuleUrl('buyer_profile_edit', $queryParameters);
	}

	protected static function extractUserFieldUserId($userField)
	{
		if (isset($userField['SETTINGS']['USER_ID']))
		{
			$result = (int)$userField['SETTINGS']['USER_ID'];
		}
		else
		{
			$result = null;
		}

		return $result;
	}

	protected static function extractUserFieldPersonType($userField)
	{
		$fieldPersonType = static::getUserFieldPersonTypeField($userField);
		$result = null;

		if ($fieldPersonType !== null && isset($userField['ROW'][$fieldPersonType]))
		{
			$result = $userField['ROW'][$fieldPersonType];
		}
		else if (isset($userField['SETTINGS']['PERSON_TYPE_DEFAULT']))
		{
			$result = $userField['SETTINGS']['PERSON_TYPE_DEFAULT'];
		}

		return $result;
	}

	protected static function getUserFieldPersonTypeField($userField)
	{
		$result = null;

		if (isset($userField['SETTINGS']['PERSON_TYPE_FIELD']))
		{
			$result = $userField['SETTINGS']['PERSON_TYPE_FIELD'];
		}

		return $result;
	}

	public static function getVariants($userId, $personType)
	{
		$key = $userId . ':' . $personType;

		if (!isset(static::$variants[$key]))
		{
			static::$variants[$key] = static::loadVariants($userId, $personType);
		}

		return static::$variants[$key];
	}

	protected static function loadVariants($userId, $personType)
	{
		$environment = Market\Trading\Entity\Manager::createEnvironment();

		try
		{
			$profile = $environment->getProfile();
			$result = $profile->getEnum($userId, $personType);
		}
		catch (Market\Exceptions\NotImplemented $exception)
		{
			$result = [];
		}

		return $result;
	}
}