<?php

namespace Yandex\Market\Export\Xml\Tag\PriceOption;

use Yandex\Market;

class OldPrice extends Market\Export\Xml\Tag\OldPrice
{
	public function getSourceRecommendation(array $context = [])
	{
		$result = parent::getSourceRecommendation($context);

		foreach ($result as &$source)
		{
			$source['TYPE'] = Market\Export\Entity\Manager::TYPE_CATALOG_PRICE_MATRIX;
		}
		unset($source);

		return $result;
	}

	public function getLangKey()
	{
		return 'EXPORT_TAG_PRICE_OPTION_OLDPRICE';
	}
}