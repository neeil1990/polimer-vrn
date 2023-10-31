<?php

namespace Yandex\Market\Export\Xml\Tag;

use Yandex\Market;

class MinQuantity extends Base
{
	use Concerns\HasPackUnitDependency;
	use Concerns\HasPackUnit;

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

	public function extendTagDescriptionList(&$tagDescriptionList, array $context)
	{
		parent::extendTagDescriptionList($tagDescriptionList, $context);
		$this->copyPricePackUnitSetting($tagDescriptionList, $context);
	}

	public function validate($value, array $context, $siblingsValues = null, Market\Result\XmlNode $nodeResult = null, $settings = null)
	{
		$this->resolveValueRatio($settings);

		return parent::validate($value, $context, $siblingsValues, $nodeResult, $settings);
	}

	protected function formatValue($value, array $context = [], Market\Result\XmlNode $nodeResult = null, $settings = null)
	{
		$this->resolveValueRatio($settings);

		return parent::formatValue($value, $context, $nodeResult, $settings);
	}

	protected function isPackRatioInverted()
	{
		return true;
	}
}