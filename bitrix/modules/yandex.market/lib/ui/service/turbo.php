<?php

namespace Yandex\Market\Ui\Service;

use Yandex\Market;
use Bitrix\Main;

class Turbo extends AbstractService
{
	use Market\Reference\Concerns\HasLang;

	protected static function includeMessages()
	{
		Main\Localization\Loc::loadMessages(__FILE__);
	}

	public function getTitle($version = '')
	{
		$suffix = $version !== '' ? '_' . $version : '';

		return static::getLang('UI_SERVICE_TURBO_TITLE' . $suffix);
	}

	public function getExportServices()
	{
		return [
			Market\Export\Xml\Format\Manager::EXPORT_SERVICE_TURBO,
		];
	}

	public function getExportSetupDisabledFields()
	{
		return [
			'SHOP_DATA',
			'ENABLE_AUTO_DISCOUNTS',
			'ENABLE_CPA',
		];
	}

	public function getTradingServices()
	{
		return [
			Market\Trading\Service\Manager::SERVICE_TURBO,
		];
	}
}