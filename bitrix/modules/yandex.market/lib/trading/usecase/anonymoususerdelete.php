<?php

namespace Yandex\Market\Trading\UseCase;

use Yandex\Market;
use Bitrix\Main;

class AnonymousUserDelete extends Market\Reference\Event\Regular
{
	use Market\Reference\Concerns\HasLang;

	protected static function includeMessages()
	{
		Main\Localization\Loc::loadMessages(__FILE__);
	}

	public static function getHandlers()
	{
		return [
			[
				'module' => 'main',
				'event' => 'OnBeforeUserDelete',
			],
		];
	}

	public static function OnBeforeUserDelete($id)
	{
		global $APPLICATION;

		$id = (int)$id;
		$usedIds = Market\Trading\Facade\AnonymousUser::getUsedIds();
		$result = true;

		if (in_array($id, $usedIds, true))
		{
			$result = false;
			$APPLICATION->ThrowException(static::getLang('TRADING_USE_CASE_ANONYMOUS_USER_DELETE_ERROR_USED', [
				'#USER_ID#' => $id,
			]));
		}

		return $result;
	}
}