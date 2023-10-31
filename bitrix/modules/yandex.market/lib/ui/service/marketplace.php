<?php

namespace Yandex\Market\Ui\Service;

use Yandex\Market;
use Bitrix\Main;

class Marketplace extends AbstractService
{
	use Market\Reference\Concerns\HasLang;

	protected static function includeMessages()
	{
		Main\Localization\Loc::loadMessages(__FILE__);
	}

	public function getTitle($version = '')
	{
		$suffix = $version !== '' ? '_' . $version : '';

		return static::getLang('UI_SERVICE_MARKETPLACE_TITLE' . $suffix);
	}

	public function getExportServices()
	{
		return [];
	}

	public function getTradingServices()
	{
		return [
			Market\Trading\Service\Manager::SERVICE_MARKETPLACE,
			Market\Trading\Service\Manager::SERVICE_BERU,
		];
	}
}