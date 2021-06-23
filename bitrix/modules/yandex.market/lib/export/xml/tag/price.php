<?php

namespace Yandex\Market\Export\Xml\Tag;

use Yandex\Market;

class Price extends Base
{
	public function getDefaultParameters()
	{
		return [
			'name' => 'price',
			'value_type' => Market\Type\Manager::TYPE_NUMBER,
			'value_positive' => true
		];
	}

	public function getSourceRecommendation(array $context = [])
	{
		$result = [];

		if ($context['HAS_CATALOG'])
		{
			$result[] = [
				'TYPE' => Market\Export\Entity\Manager::TYPE_CATALOG_PRICE,
				'FIELD' => 'MINIMAL.DISCOUNT_VALUE'
			];

			$result[] = [
				'TYPE' => Market\Export\Entity\Manager::TYPE_CATALOG_PRICE,
				'FIELD' => 'OPTIMAL.DISCOUNT_VALUE'
			];

			$result[] = [
				'TYPE' => Market\Export\Entity\Manager::TYPE_CATALOG_PRICE,
				'FIELD' => 'BASE.DISCOUNT_VALUE'
			];
		}

		return $result;
	}

	public function compareValue($value, array $context = [], Market\Result\XmlValue $nodeValue = null)
	{
		if ($nodeValue !== null)
		{
			$tagCurrencyId = (string)$nodeValue->getTagValue('currencyId');

			if ($tagCurrencyId !== '')
			{
				$currencyId = (string)Market\Data\Currency::getCurrency($tagCurrencyId);
				$baseCurrencyId = (string)Market\Data\Currency::getBaseCurrency();

				$value = Market\Data\Currency::convert($value, $currencyId, $baseCurrencyId);
			}
		}

		return $this->formatValue($value);
	}
}