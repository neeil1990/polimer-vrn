<?php

namespace Yandex\Market\Export\Xml\Tag;

use Yandex\Market\Type;
use Yandex\Market\Export\Xml;
use Yandex\Market\Ui\UserField;

class ConditionQuality extends Base
{
	public function getDefaultParameters()
	{
		return [
			'name' => 'quality',
			'value_type' => Type\Manager::TYPE_ENUM,
			'value_listing' => new Xml\Listing\ConditionQuality(),
		];
	}

	public function getSourceRecommendation(array $context = [])
	{
		$userTypes = [
			UserField\ConditionQuality\Property::USER_TYPE,
		];

		return Xml\Routine\Recommendation\Property::userTypeValue($userTypes, $context);
	}
}