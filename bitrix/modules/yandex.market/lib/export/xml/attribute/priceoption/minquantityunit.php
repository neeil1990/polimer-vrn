<?php

namespace Yandex\Market\Export\Xml\Attribute\PriceOption;

use Yandex\Market;

class MinQuantityUnit extends Market\Export\Xml\Attribute\Base
{
	use Market\Reference\Concerns\HasMessage;

	public function getDefaultParameters()
	{
		return [
			'name' => 'unit',
		];
	}

	public function getSourceRecommendation(array $context = [])
	{
		return array_merge(
			$this->getCommonRecommendation($context),
			$this->getCatalogRecommendation($context)
		);
	}

	protected function getCatalogRecommendation(array $context)
	{
		if (empty($context['HAS_CATALOG'])) { return []; }

		return [
			[
				'TYPE' => Market\Export\Entity\Manager::TYPE_CATALOG_PRODUCT,
				'FIELD' => 'MEASURE_TITLE',
			],
		];
	}

	protected function getCommonRecommendation(array $context)
	{
		return [
			[
				'TYPE' => Market\Export\Entity\Manager::TYPE_TEXT,
				'VALUE' => self::getMessage('VALUE_ITEM'),
			],
		];
	}
}