<?php

namespace Yandex\Market\Ui\UserField\ConditionQuality;

use Yandex\Market\Reference\Concerns;
use Yandex\Market\Ui\UserField;
use Yandex\Market\Export\Xml;

class Property extends UserField\Listing\Property
{
	use Concerns\HasMessage;

	const USER_TYPE = 'ym_condition_quality';

	protected static function userType()
	{
		return static::USER_TYPE;
	}

	protected static function listing()
	{
		return new Xml\Listing\ConditionQuality();
	}
}