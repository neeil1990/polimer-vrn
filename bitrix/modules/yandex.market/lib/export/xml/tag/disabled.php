<?php

namespace Yandex\Market\Export\Xml\Tag;

use Bitrix\Main;
use Yandex\Market;

class Disabled extends Base
{
	public function getDefaultParameters()
	{
		return [
			'name' => 'disabled',
			'value_type' => Market\Type\Manager::TYPE_BOOLEAN,
		];
	}

	public function getSourceRecommendation(array $context = [])
	{
		$result = [];

		if ($context['HAS_CATALOG'])
		{
			$result[] = [
				'TYPE' => Market\Export\Entity\Manager::TYPE_FORMULA,
				'FIELD' => [
					'FUNCTION' => Market\Template\Functions\Registry::FUNCTION_NOT,
					'PARTS' => [
						sprintf('%s.%s', Market\Export\Entity\Manager::TYPE_CATALOG_PRODUCT, 'AVAILABLE'),
					],
				],
			];
		}

		return $result;
	}
}