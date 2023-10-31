<?php

namespace Yandex\Market\Trading\Service\Beru;

use Bitrix\Main;
use Yandex\Market;

/** @deprecated */
class Info extends Market\Trading\Service\Marketplace\Info
{
	protected static function includeMessages()
	{
		Main\Localization\Loc::loadMessages(__FILE__);
		parent::includeMessages();
	}

	public function getTitle($version = '')
	{
		$suffix = $version !== '' ? '_' . $version : '';

		return static::getLang('TRADING_SERVICE_BERU_INFO_TITLE' . $suffix, null, $this->provider->getCode());
	}

	public function getDescription()
	{
		return static::getLang('TRADING_SERVICE_BERU_INFO_DESCRIPTION', null, '');
	}

	public function getLogoPath()
	{
		return Market\Ui\Assets::getRootDirectoryPath('images') . '/assets/beru.svg';
	}

	public function getMessage($code, $replaces = null, $fallback = null)
	{
		return static::getLang('TRADING_SERVICE_BERU_INFO_' . $code, $replaces, $fallback);
	}

	public function getProfileValues()
	{
		return [
			'NAME' => static::getLang('TRADING_SERVICE_BERU_PROFILE_NAME'),
		];
	}

	public function getAnonymousUserData()
	{
		return [
			'NAME' => static::getLang('TRADING_SERVICE_BERU_USER'),
		];
	}

	public function getUserGroupData()
	{
		return [
			'NAME' => static::getLang('TRADING_SERVICE_BERU_USER_GROUP'),
		];
	}
}
