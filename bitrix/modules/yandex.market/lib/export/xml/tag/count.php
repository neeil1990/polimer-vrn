<?php

namespace Yandex\Market\Export\Xml\Tag;

use Yandex\Market;

class Count extends Base
{
	public function getDefaultParameters()
	{
		return [
			'name' => 'count',
			'value_type' => Market\Type\Manager::TYPE_COUNT,
		];
	}

	public function getSourceRecommendation(array $context = [])
	{
		$result = [];

		if ($context['HAS_CATALOG'])
		{
			$result[] = [
				'TYPE' => Market\Export\Entity\Manager::TYPE_CATALOG_PRODUCT,
				'FIELD' => 'QUANTITY'
			];
		}

		return $result;
	}
}