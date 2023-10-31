<?php

namespace Yandex\Market\Export\Xml\Tag;

use Yandex\Market\Type;
use Yandex\Market\Export\Xml;
use Yandex\Market\Ui\UserField;

class ConditionReason extends Base
{
	public function getDefaultParameters()
	{
		return [
			'name' => 'reason',
			'max_length' => 3000,
			'value_type' => Type\Manager::TYPE_HTML,
		];
	}

	public function getSourceRecommendation(array $context = [])
	{
		$userTypes = [
			UserField\ConditionType\Property::USER_TYPE,
			UserField\ConditionQuality\Property::USER_TYPE,
		];

		return Xml\Routine\Recommendation\Property::userTypeDescription($userTypes, $context);
	}
}