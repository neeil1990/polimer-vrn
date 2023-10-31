<?php

namespace Yandex\Market\Export\Xml\Attribute\PriceOption;

use Yandex\Market;

class DiscountUnit extends Market\Export\Xml\Attribute\Base
{
	public function getDefaultParameters()
	{
		return [
			'name' => 'unit',
		];
	}

	public function getSourceRecommendation(array $context = [])
	{
		return [
			[
				'TYPE' => Market\Export\Entity\Manager::TYPE_TEXT,
				'VALUE' => '%',
			],
			[
				'TYPE' => Market\Export\Entity\Manager::TYPE_TEXT,
				'VALUE' => 'abs',
			],
		];
	}

	public function getLangKey()
	{
		return 'EXPORT_ATTRIBUTE_PRICE_OPTION_DISCOUNT_UNIT';
	}
}