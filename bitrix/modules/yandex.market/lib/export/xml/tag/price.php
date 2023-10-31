<?php

namespace Yandex\Market\Export\Xml\Tag;

use Yandex\Market;

class Price extends Base
{
	use Market\Reference\Concerns\HasMessage;
	use Concerns\HasPackUnit;

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

	public function validate($value, array $context, $siblingsValues = null, Market\Result\XmlNode $nodeResult = null, $settings = null)
	{
		$this->resolveValueRatio($settings);

		return (
			parent::validate($value, $context, $siblingsValues, $nodeResult, $settings)
			&& $this->validateRatio($context, $nodeResult, $settings)
		);
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

	protected function formatValue($value, array $context = [], Market\Result\XmlNode $nodeResult = null, $settings = null)
	{
		$this->resolveValueRatio($settings);

		return parent::formatValue($value, $context, $nodeResult, $settings);
	}

	public function getSettingsDescription(array $context = [])
	{
		$result = [
			'PACK_RATIO' => [
				'TITLE' => self::getMessage('SETTINGS_PACK_RATIO'),
				'DESCRIPTION' => self::getMessage('SETTINGS_PACK_RATIO_HELP'),
				'TYPE' => 'param',
			],
		];

		if (Market\Config::isExpertMode())
		{
			$result['USER_GROUP'] = [
				'TITLE' => self::getMessage('SETTINGS_USER_GROUP'),
				'TYPE' => 'enumeration',
				'VALUES' => $this->getUserGroupEnum(),
			];
		}

		return $result;
	}

	protected function getUserGroupEnum()
	{
		$defaults = Market\Data\UserGroup::getDefaults();
		$defaultsMap = array_flip($defaults);
		$enum = Market\Data\UserGroup::getEnum();

		uasort($enum, static function($aOption, $bOption) use ($defaultsMap) {
			$aSort = (int)isset($defaultsMap[$aOption['ID']]);
			$bSort = (int)isset($defaultsMap[$bOption['ID']]);

			if ($aSort === $bSort) { return 0; }

			return ($aSort > $bSort ? -1 : 1);
		});

		return $enum;
	}
}