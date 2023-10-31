<?php

namespace Yandex\Market\Ui\Checker;

use Bitrix\Main;
use Yandex\Market;

class Notify
{
	use Market\Reference\Concerns\HasLang;

	const ERROR_TAG = 'YANDEX_MARKET_CHECKER_ERROR';

	protected static function includeMessages()
	{
		Main\Localization\Loc::loadMessages(__FILE__);
	}

	public static function error()
	{
		$url = Market\Ui\Admin\Path::getModuleUrl('checker', [
			'lang' => LANGUAGE_ID,
			'autostart' => 'Y',
		]);
		$message = static::getLang('CHECKER_NOTIFY_ERROR', [
			'#URL#' => $url,
		]);

		\CAdminNotify::Add([
			'TAG' => static::ERROR_TAG,
			'NOTIFY_TYPE' => \CAdminNotify::TYPE_ERROR,
			'MESSAGE' => $message,
			'MODULE_ID' => Market\Config::getModuleName(),
		]);
	}

	public static function closeError()
	{
		\CAdminNotify::DeleteByTag(static::ERROR_TAG);
	}
}