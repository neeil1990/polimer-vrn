<?php

namespace Yandex\Market\Export\Xml\Tag;

use Bitrix\Main;
use Yandex\Market;

Main\Localization\Loc::loadMessages(__FILE__);

class Dimensions extends Base
{
	public function getDefaultParameters()
	{
		return [
			'name' => 'dimensions',
			'value_type' => Market\Type\Manager::TYPE_DIMENSIONS,
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
				'FIELD' => 'YM_SIZE'
			];
		}

		return $result;
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

	protected function getUnitMap()
	{
		return [
			'mm' => 0.1,
			'cm' => 1,
			'dm' => 10,
			'm' => 100,
		];
	}
}