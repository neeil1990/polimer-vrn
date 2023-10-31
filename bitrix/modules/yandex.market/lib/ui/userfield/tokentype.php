<?php

namespace Yandex\Market\Ui\UserField;

use Yandex\Market;
use Bitrix\Main;

Main\Localization\Loc::loadMessages(__FILE__);

class TokenType extends EnumerationType
{
	protected static $variants;

	public static function GetList($arUserField)
	{
		$clientId = static::extractUserFieldClientId($arUserField);
		$scope = $arUserField['SETTINGS']['SCOPE'];
		$variants = static::getVariants($clientId, $scope);

		$result = new \CDBResult();
		$result->InitFromArray($variants);

		return $result;
	}

	public static function GetEditFormHTML($arUserField, $arHtmlControl)
	{
		$arUserField = static::extendUserField($arUserField);

		$result = parent::GetEditFormHTML($arUserField, $arHtmlControl);
		$result .= ' ';
		$result .= static::getRefreshButton($arUserField, $arHtmlControl['NAME']);

		static::loadLangMessages();

		return $result;
	}

	protected static function extendUserField($userField)
	{
		$defaultSettings = [
			'CAPTION_NO_VALUE' => Market\Config::getLang('USER_FIELD_TOKEN_NEW'),
			'ALLOW_NO_VALUE' => 'Y',
		];

		$userField['SETTINGS'] = (isset($userField['SETTINGS']) ? $userField['SETTINGS'] + $defaultSettings : $defaultSettings);

		return $userField;
	}

	protected static function getRefreshButton($userField, $name)
	{
		$htmlId = static::convertHtmlNameToId($name) . '_REFRESH';

		return
			static::getRefreshButtonHtml($htmlId)
			. static::getRefreshButtonScript($userField, $htmlId);
	}

	protected static function getRefreshButtonHtml($htmlId)
	{
		return '<input type="button" value="' . htmlspecialcharsbx(Market\Config::getLang('USER_FIELD_TOKEN_REFRESH')) . '" id="' . htmlspecialcharsbx($htmlId) . '" />';
	}

	protected static function getRefreshButtonScript($userField, $htmlId)
	{
		$clientIdField = static::getUserFieldClientIdField($userField);
		$clientPasswordField = static::getUserFieldClientPasswordField($userField);
		$callbackPath = static::getCallbackPath();
		$callbackUrl = Market\Utils\Url::absolutizePath($callbackPath);
		$authRequest = new Market\Api\OAuth2\VerificationCode\Request();
		$authRequest->setOauthClientId('OAUTH_CLIENT_ID_HOLDER');
		$authRequest->setScope('OAUTH_SCOPE_HOLDER');
		$authRequest->setRedirectUri($callbackUrl);

		Market\Ui\Assets::loadPlugin('Ui.Input.TokenRefresh');

		$html = Market\Ui\Assets::initPlugin('Ui.Input.TokenRefresh', '#' . $htmlId, [
			'authUrl' => $authRequest->getFullUrl(),
			'refreshUrl' => static::getRefreshPath(),
			'exchangeUrl' => static::getExchangePath(),
			'scope' => static::normalizeScope($userField['SETTINGS']['SCOPE']),
			'oauthClientIdElement' => 'input[name="' . htmlspecialcharsbx($clientIdField) .'"]',
			'oauthClientPasswordElement' => 'input[name="' . htmlspecialcharsbx($clientPasswordField) .'"]',
		]);

		return $html;
	}

	protected static function loadLangMessages()
	{
		Market\Ui\Assets::loadMessages([
			'USER_FIELD_TOKEN_EXCHANGE_CODE_FAIL',
			'USER_FIELD_TOKEN_UNDEFINED_ERROR',
			'USER_FIELD_TOKEN_REFRESH_FAIL',
		]);
	}

	protected static function convertHtmlNameToId($name)
	{
		$result = str_replace(['[', ']', '-', '__'], '_', $name);
		$result = trim($result, '_');

		return $result;
	}

	public static function getCallbackPath()
	{
		return BX_ROOT . '/tools/' . Market\Config::getModuleName() . '/oauth2/callback.php';
	}

	public static function getRefreshPath()
	{
		return BX_ROOT . '/tools/' . Market\Config::getModuleName() . '/oauth2/tokenenum.php';
	}

	public static function getExchangePath()
	{
		return BX_ROOT . '/tools/' . Market\Config::getModuleName() . '/oauth2/exchangecode.php';
	}

	protected static function getUserFieldClientIdField($userField)
	{
		$result = null;

		if (isset($userField['SETTINGS']['CLIENT_ID_FIELD']))
		{
			$result = $userField['SETTINGS']['CLIENT_ID_FIELD'];
		}

		return $result;
	}

	protected static function getUserFieldClientPasswordField($userField)
	{
		$result = null;

		if (isset($userField['SETTINGS']['CLIENT_PASSWORD_FIELD']))
		{
			$result = $userField['SETTINGS']['CLIENT_PASSWORD_FIELD'];
		}

		return $result;
	}

	protected static function extractUserFieldClientId($userField)
	{
		$result = null;
		$clientIdField = static::getUserFieldClientIdField($userField);

		if ($clientIdField !== null && isset($userField['ROW'][$clientIdField]))
		{
			$result = $userField['ROW'][$clientIdField];
		}

		return $result;
	}

	public static function getVariants($clientId, $scope)
	{
		$clientId = trim($clientId);
		$scopeNormalized = static::normalizeScope($scope);
		$cacheKey = $clientId . ':' . $scopeNormalized;

		if ($clientId === '' || $scopeNormalized === '')
		{
			$result = [];
		}
		else if (isset(static::$variants[$cacheKey]))
		{
			$result = static::$variants[$cacheKey];
		}
		else
		{
			$result = static::loadVariants($clientId, $scopeNormalized);

			static::$variants[$cacheKey] = $result;
		}

		return $result;
	}

	protected static function loadVariants($clientId, $scope)
	{
		$result = [];

		$query = Market\Api\OAuth2\Token\Table::getList([
			'filter' => [ '=CLIENT_ID' => $clientId, '=SCOPE' => $scope, ],
			'select' => [ 'ID', 'USER_ID', 'USER_LOGIN' ]
		]);

		while ($token = $query->fetch())
		{
			$result[] = [
				'ID' => $token['ID'],
				'VALUE' => $token['USER_LOGIN'] ?: $token['USER_ID'],
			];
		}

		return $result;
	}

	protected static function normalizeScope($scope)
	{
		return is_array($scope) ? implode(' ', $scope) : (string)$scope;
	}
}