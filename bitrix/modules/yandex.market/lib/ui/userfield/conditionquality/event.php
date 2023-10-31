<?php

namespace Yandex\Market\Ui\UserField\ConditionQuality;

use Yandex\Market;

class Event extends Market\Reference\Event\Regular
{
	public static function getHandlers()
	{
		return [
			[
				'module' => 'iblock',
				'event' => 'OnIBlockPropertyBuildList'
			]
		];
	}

	public static function OnIBlockPropertyBuildList()
	{
		return Property::GetUserTypeDescription();
	}
}