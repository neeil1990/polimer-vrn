<?php

namespace Yandex\Market\Trading\Service\MarketplaceDbs;

use Yandex\Market;
use Bitrix\Main;

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
		$result = (string)static::getLang('TRADING_SERVICE_MARKETPLACE_DBS_INFO_TITLE' . $suffix, null, '');

		if ($result === '')
		{
			$result = parent::getTitle($version);
		}

		return $result;
	}

	public function getMessage($code, $replaces = null, $fallback = null)
	{
		$result = (string)static::getLang('TRADING_SERVICE_MARKETPLACE_DBS_INFO_' . $code, $replaces, '');

		if ($result === '')
		{
			$result = parent::getMessage($code, $replaces, $fallback);
		}

		return $result;
	}
}