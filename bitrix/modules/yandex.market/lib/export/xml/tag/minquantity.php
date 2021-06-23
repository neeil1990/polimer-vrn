<?php

namespace Yandex\Market\Export\Xml\Tag;

use Bitrix\Main;
use Yandex\Market;

class MinQuantity extends Base
{
	public function getDefaultParameters()
	{
		return [
			'name' => 'min-quantity',
			'value_type' => Market\Type\Manager::TYPE_NUMBER,
		];
	}

	public function getSourceRecommendation(array $context = [])
	{
		$result = [];

		if ($context['HAS_CATALOG'])
		{
			$result[] = [
				'TYPE' => Market\Export\Entity\Manager::TYPE_CATALOG_PRODUCT,
				'FIELD' => 'MEASURE_RATIO'
			];
		}

		return $result;
	}
}