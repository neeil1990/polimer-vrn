<?php

namespace Yandex\Market\Export\Xml\Tag\PriceOption;

use Yandex\Market;

class Price extends Market\Export\Xml\Tag\Price
{
	use Market\Export\Xml\Tag\Concerns\HasPackUnitDependency;

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

	public function extendTagDescriptionList(&$tagDescriptionList, array $context)
	{
		parent::extendTagDescriptionList($tagDescriptionList, $context);
		$this->copyPricePackUnitSetting($tagDescriptionList, $context);
	}

	public function getSettingsDescription(array $context = [])
	{
		return [];
	}

	public function getLangKey()
	{
		return 'EXPORT_TAG_PRICE_OPTION_PRICE';
	}
}