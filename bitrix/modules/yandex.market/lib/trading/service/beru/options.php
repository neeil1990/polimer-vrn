<?php

namespace Yandex\Market\Trading\Service\Beru;

use Bitrix\Main;
use Yandex\Market;

/** @deprecated */
class Options extends Market\Trading\Service\Marketplace\Options
{
	protected static function includeMessages()
	{
		Main\Localization\Loc::loadMessages(__FILE__);
		parent::includeMessages();
	}

	public function getTitle($version = '')
	{
		return static::getLang('TRADING_SERVICE_BERU_TITLE');
	}
}
