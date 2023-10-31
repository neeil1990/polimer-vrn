<?php

namespace Yandex\Market\Export\Xml\Tag;

use Yandex\Market;

class Expiry extends Base
{
	use Market\Reference\Concerns\HasMessage;

	public function getDefaultParameters()
	{
		return [
			'name' => 'expiry',
			'value_type' => Market\Type\Manager::TYPE_DATEPERIOD,
			'date_format' => 'Y-m-d\TH:i'
		];
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
		$this->parameters['value_unit'] = isset($settings['UNIT']) && $settings['UNIT'] !== 'plain'
			? $settings['UNIT']
			: null;
	}

	public function getSettingsDescription(array $context = [])
	{
		return [
			'UNIT' => [
				'TITLE' => self::getMessage('SETTINGS_UNIT'),
				'DESCRIPTION' => self::getMessage('SETTINGS_UNIT_HELP'),
				'TYPE' => 'enumeration',
				'VALUES' => $this->getUnitEnum(),
			],
		];
	}

	protected function getUnitEnum()
	{
		$result = [];
		$options = [
			Market\Type\PeriodType::UNIT_DAY,
			Market\Type\PeriodType::UNIT_MONTH,
			Market\Type\PeriodType::UNIT_YEAR,
			Market\Type\PeriodType::UNIT_HOUR,
			'plain',
		];

		foreach ($options as $option)
		{
			$result[] = [
				'ID' => $option,
				'VALUE' => self::getMessage('SETTINGS_UNIT_VALUE_' . Market\Data\TextString::toUpper($option)),
			];
		}

		return $result;
	}
}