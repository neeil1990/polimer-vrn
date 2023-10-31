<?php

namespace Yandex\Market\Export\Xml\Tag;

use Yandex\Market;
use Bitrix\Main;

Main\Localization\Loc::loadMessages(__FILE__);

class Weight extends Base
{
	public function getDefaultParameters()
	{
		return [
			'name' => 'weight',
			'value_type' => Market\Type\Manager::TYPE_NUMBER,
			'value_positive' => true,
			'value_precision' => 3,
			'value_ratio' => 1,
		];
	}

	public function getSourceRecommendation(array $context = [])
	{
		$result = [];

		if ($context['HAS_CATALOG'])
		{
		    $result[] = [
				'TYPE' => Market\Export\Entity\Manager::TYPE_CATALOG_PRODUCT,
				'FIELD' => 'WEIGHT'
			];
		}

		return $result;
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

	protected function resolveValueRatio($settings)
	{
		$this->parameters['value_ratio'] = isset($settings['BITRIX_UNIT'])
			? $this->getUnitRatio($settings['BITRIX_UNIT'])
			: 1;
	}

	public function getUnitRatio($unit)
	{
		$map = $this->getUnitMap();

		return isset($map[$unit]) ? (float)$map[$unit] : 1;
	}

	public function getSettingsDescription(array $context = [])
	{
		$langKey = $this->getLangKey();

		$result = [
			'BITRIX_UNIT' => [
				'TITLE' => Market\Config::getLang($langKey . '_SETTINGS_BITRIX_UNIT_TITLE'),
				'TYPE' => 'enumeration',
				'VALUES' => []
			]
		];

		// fill unit

		$unitMap = $this->getUnitMap();

		foreach ($unitMap as $unit => $ratio)
		{
			$result['BITRIX_UNIT']['VALUES'][] = [
				'ID' => $unit,
				'VALUE' => Market\Config::getLang($langKey . '_SETTINGS_BITRIX_UNIT_ENUM_' . Market\Data\TextString::toUpper($unit))
			];
		}

		return $result;
	}

	protected function getUnitMap()
	{
		return [
			'gram' => 0.001,
			'kilogram' => 1,
			'centner' => 100,
			'ton' => 1000,
			'milligram' => 0.000001
		];
	}
}