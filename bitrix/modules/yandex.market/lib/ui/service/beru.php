<?php

namespace Yandex\Market\Ui\Service;

use Yandex\Market;
use Bitrix\Main;

class Beru extends AbstractService
{
	use Market\Reference\Concerns\HasLang;

	protected static function includeMessages()
	{
		Main\Localization\Loc::loadMessages(__FILE__);
	}

	public function getTitle($version = '')
	{
		$suffix = $version !== '' ? '_' . $version : '';

		return static::getLang('UI_SERVICE_BERU_TITLE' . $suffix);
	}

	public function getExportServices()
	{
		return [
			// Market\Export\Xml\Format\Manager::EXPORT_SERVICE_BERU_RU,
		];
	}

	public function getTradingServices()
	{
		return [
			Market\Trading\Service\Manager::SERVICE_BERU,
		];
	}
}