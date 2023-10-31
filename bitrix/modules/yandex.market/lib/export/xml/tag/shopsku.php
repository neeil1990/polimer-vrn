<?php

namespace Yandex\Market\Export\Xml\Tag;

use Yandex\Market;

class ShopSku extends Base
{
	public function getDefaultParameters()
	{
		return [ 'name' => 'shop-sku' ];
	}

	public function getSourceRecommendation(array $context = [])
	{
		$result = [];

		if ($context['HAS_OFFER'])
		{
			$result[] = [
				'TYPE' => Market\Export\Entity\Manager::TYPE_IBLOCK_OFFER_FIELD,
				'FIELD' => 'ID'
			];
		}
		else
		{
			$result[] = [
				'TYPE' => Market\Export\Entity\Manager::TYPE_IBLOCK_ELEMENT_FIELD,
				'FIELD' => 'ID'
			];
		}

		return $result;
	}
}