<?php

namespace Yandex\Market\Export\Xml\Tag\PriceOption;

use Yandex\Market;

class MinQuantity extends Market\Export\Xml\Tag\MinQuantity
{
	public function getSourceRecommendation(array $context = [])
	{
		if (!$context['HAS_CATALOG']) { return []; }

		return [
			[
				'TYPE' => Market\Export\Entity\Manager::TYPE_CATALOG_PRICE_MATRIX,
				'FIELD' => 'MINIMAL.QUANTITY_FROM',
			],
			[
				'TYPE' => Market\Export\Entity\Manager::TYPE_CATALOG_PRICE_MATRIX,
				'FIELD' => 'OPTIMAL.QUANTITY_FROM',
			],
			[
				'TYPE' => Market\Export\Entity\Manager::TYPE_CATALOG_PRICE_MATRIX,
				'FIELD' => 'BASE.QUANTITY_FROM',
			],
		];
	}

	public function getLangKey()
	{
		return 'EXPORT_TAG_PRICE_OPTION_MIN_QUANTITY';
	}
}