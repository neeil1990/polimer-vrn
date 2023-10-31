<?php

namespace Yandex\Market\Export\Xml\Tag;

use Yandex\Market;

class PurchasePrice extends Price
{
	public function getDefaultParameters()
	{
		return [ 'name' => 'purchase_price' ] + parent::getDefaultParameters();
	}

	public function getSourceRecommendation(array $context = [])
	{
		$result = [];

		if ($context['HAS_CATALOG'])
		{
			$result[] = [
				'TYPE' => Market\Export\Entity\Manager::TYPE_CATALOG_PRODUCT,
				'FIELD' => 'PURCHASING_PRICE_RUR'
			];

			$result[] = [
				'TYPE' => Market\Export\Entity\Manager::TYPE_CATALOG_PRODUCT,
				'FIELD' => 'PURCHASING_PRICE'
			];
		}

		return $result;
	}

	public function getSettingsDescription(array $context = [])
	{
		return [];
	}
}