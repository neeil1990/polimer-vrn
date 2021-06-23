<?php

namespace Yandex\Market\Ui\Checker;

use Bitrix\Main;
use Yandex\Market;

class Announcement
{
	use Market\Reference\Concerns\HasLang;

	protected static function includeMessages()
	{
		Main\Localization\Loc::loadMessages(__FILE__);
	}

	public static function show()
	{
		echo BeginNote('style="max-width: 600px;"');
		echo static::getLang('CHECKER_ANNOUNCEMENT', [
			'#CHECKER_URL#' => Market\Ui\Admin\Path::getModuleUrl('checker', [
				'lang' => LANGUAGE_ID,
				'autostart' => 'Y',
			]),
		]);
		echo EndNote();
	}
}