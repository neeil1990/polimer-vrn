<?php

namespace Yandex\Market\Export\Xml\Tag;

use Yandex\Market;

class Description extends Base
{
	public function getDefaultParameters()
	{
		return [
			'name' => 'description',
			'value_type' => Market\Type\Manager::TYPE_HTML,
			'max_length' => 6000
		];
	}

	public function getSourceRecommendation(array $context = [])
	{
		$partials = [
			$this->getElementRecommendation(),
			$this->getOfferRecommendation($context),
		];

		if ($context['EXPORT_SERVICE'] === Market\Export\Xml\Format\Manager::EXPORT_SERVICE_TURBO)
		{
			foreach ($partials as &$partial)
			{
				$partial = array_reverse($partial);
			}
			unset($partial);
		}

		return array_merge(...$partials);
	}

	protected function getElementRecommendation()
	{
		return [
			[
				'TYPE' => Market\Export\Entity\Manager::TYPE_IBLOCK_ELEMENT_FIELD,
				'FIELD' => 'PREVIEW_TEXT'
			],
			[
				'TYPE' => Market\Export\Entity\Manager::TYPE_IBLOCK_ELEMENT_FIELD,
				'FIELD' => 'DETAIL_TEXT'
			],
		];
	}

	protected function getOfferRecommendation(array $context)
	{
		if (empty($context['HAS_OFFER'])) { return []; }

		return [
			[
				'TYPE' => Market\Export\Entity\Manager::TYPE_IBLOCK_OFFER_FIELD,
				'FIELD' => 'PREVIEW_TEXT'
			],
			[
				'TYPE' => Market\Export\Entity\Manager::TYPE_IBLOCK_OFFER_FIELD,
				'FIELD' => 'DETAIL_TEXT'
			],
		];
	}
}