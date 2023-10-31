<?php

namespace Yandex\Market\Export\Xml\Tag\PriceOption;

use Yandex\Market;

class Discount extends Market\Export\Xml\Tag\Base
{
	public function getDefaultParameters()
	{
		return [
			'name' => 'discount',
			'value_type' => Market\Type\Manager::TYPE_NUMBER,
		];
	}

	public function getLangKey()
	{
		return 'EXPORT_TAG_PRICE_OPTION_DISCOUNT';
	}
}