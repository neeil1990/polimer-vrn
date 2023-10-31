<?php

namespace Yandex\Market\Export\Xml\Tag;

use Yandex\Market\Export\Xml\Routine\Recommendation;
use Yandex\Market\Reference\Concerns;

class Vendor extends Base
{
	use Concerns\HasMessage;

	public function getDefaultParameters()
	{
		return [
			'name' => 'vendor',
		];
	}

	/** @noinspection SpellCheckingInspection */
	public function getSourceRecommendation(array $context = [])
	{
		return Recommendation\Property::filter([
			'LOGIC' => 'OR',
			[ '%CODE' => [ 'MANUFACTURER', 'VENDOR', 'BRAND', 'BREND' ] ],
			[ '%NAME' => explode(',', self::getMessage('FILTER_TITLE')) ],
		], $context);
	}
}