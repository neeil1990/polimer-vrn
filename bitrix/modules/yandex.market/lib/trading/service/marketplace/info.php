<?php

namespace Yandex\Market\Trading\Service\Marketplace;

use Yandex\Market;
use Bitrix\Main;

class Info extends Market\Trading\Service\Reference\Info
{
	use Market\Reference\Concerns\HasLang;

	protected static function includeMessages()
	{
		Main\Localization\Loc::loadMessages(__FILE__);
	}

	public function getTitle($version = '')
	{
		$suffix = $version !== '' ? '_' . $version : '';

		return static::getLang('TRADING_SERVICE_MARKETPLACE_INFO_TITLE' . $suffix, null, $this->provider->getCode());
	}

	public function getDescription()
	{
		return static::getLang('TRADING_SERVICE_MARKETPLACE_INFO_DESCRIPTION', null, '');
	}

	public function getLogoPath()
	{
		return Market\Ui\Assets::getRootDirectoryPath('images') . '/assets/marketplace.svg';
	}

	public function getMessage($code, $replaces = null, $fallback = null)
	{
		return static::getLang('TRADING_SERVICE_MARKETPLACE_INFO_' . $code, $replaces, $fallback);
	}

	public function getProfileValues()
	{
		return [
			'NAME' => static::getLang('TRADING_SERVICE_MARKETPLACE_PROFILE_NAME'),
		];
	}

	public function getAnonymousUserData()
	{
		return [
			'NAME' => static::getLang('TRADING_SERVICE_MARKETPLACE_USER'),
		];
	}

	public function getUserGroupData()
	{
		return [
			'NAME' => static::getLang('TRADING_SERVICE_MARKETPLACE_USER_GROUP'),
		];
	}

	public function getCompanyData()
	{
		return [
			'TITLE' => static::getLang('TRADING_SERVICE_MARKETPLACE_COMPANY'),
		];
	}

	public function getContactData()
	{
		return [
			'NAME' => static::getLang('TRADING_SERVICE_MARKETPLACE_USER'),
		];
	}
}