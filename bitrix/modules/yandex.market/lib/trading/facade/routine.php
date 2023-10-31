<?php

namespace Yandex\Market\Trading\Facade;

use Yandex\Market;
use Yandex\Market\Trading\Setup as TradingSetup;

class Routine
{
	public static function mergeSettings(TradingSetup\Model $setup, array $overrides)
	{
		$settings = $overrides + $setup->getSettings()->getValues();

		static::writeDatabase($setup, $settings);
		static::passValues($setup, $settings);
	}

	public static function copySettings(TradingSetup\Model $from, TradingSetup\Model $to, array $overrides = [])
	{
		$settings = $overrides + $from->getSettings()->getValues();

		if ($from->getServiceCode() !== $to->getServiceCode() || $from->getBehaviorCode() !== $to->getBehaviorCode())
		{
			$options = $to->wakeupService()->getOptions();
			$fields = $options->getFields($to->getEnvironment(), $to->getSiteId());

			$settings = array_intersect_key($settings, $fields);
			$settings = static::fillSettingsDefaults($fields, $settings);
			$settings = static::sanitizeSettingsEnum($fields, $settings);
		}

		static::writeDatabase($to, $settings);
		static::passValues($to, $settings);
	}

	protected static function fillSettingsDefaults($fields, $values)
	{
		$result = $values;

		foreach ($fields as $fieldName => $field)
		{
			if (!isset($field['SETTINGS']['DEFAULT_VALUE'])) { continue; }
			if (!empty($field['SETTINGS']['READONLY'])) { continue; }

			$isHidden = (!empty($field['HIDDEN']) && $field['HIDDEN'] === 'Y');
			$defaultValue = $field['SETTINGS']['DEFAULT_VALUE'];
			$value = Market\Utils\Field::getChainValue($result, $fieldName, Market\Utils\Field::GLUE_BRACKET);

			if ($value === null || $isHidden || $fieldName === 'PERSON_TYPE')
			{
				Market\Utils\Field::setChainValue($result, $fieldName, $defaultValue, Market\Utils\Field::GLUE_BRACKET);
			}
		}

		return $result;
	}

	protected static function sanitizeSettingsEnum($fields, $values)
	{
		$result = $values;

		foreach ($fields as $fieldName => $field)
		{
			$value = Market\Utils\Field::getChainValue($result, $fieldName, Market\Utils\Field::GLUE_BRACKET);
			$userField = Market\Ui\UserField\Helper\Field::extend($field);
			$userField = Market\Ui\UserField\Helper\Field::extendValue($userField, $value, $values);
			$isMultiple = ($userField['MULTIPLE'] !== 'N');

			if (empty($userField['USER_TYPE']['CLASS_NAME'])) { continue; }
			if (!is_callable([$userField['USER_TYPE']['CLASS_NAME'], 'GetList'])) { continue; }

			$query = call_user_func([$userField['USER_TYPE']['CLASS_NAME'], 'GetList'], $userField);
			$enum = Market\Ui\UserField\Helper\Enum::toArray($query);
			$enumIds = array_column($enum, 'ID');
			$valueIds = $isMultiple && is_array($value) ? $value : [ $value ];
			$existIds = array_intersect($valueIds, $enumIds);

			if (!empty($existIds)) { continue; }

			if (!empty($userField['SETTINGS']['DEFAULT_VALUE']))
			{
				$defaultValue = $userField['SETTINGS']['DEFAULT_VALUE'];
				$enumDefaultIds = $isMultiple && is_array($defaultValue) ? $defaultValue : [ $defaultValue ];
			}
			else
			{
				$enumDefaults = array_filter($enum, static function($option) {
					return isset($option['DEF']) && $option['DEF'] === 'Y';
				});
				$enumDefaultIds = array_column($enumDefaults, 'ID');
			}

			if (empty($enumDefaultIds))
			{
				Market\Utils\Field::unsetChainValue($result, $fieldName, Market\Utils\Field::GLUE_BRACKET);
			}
			else if ($isMultiple)
			{
				Market\Utils\Field::setChainValue($result, $fieldName, $enumDefaultIds, Market\Utils\Field::GLUE_BRACKET);
			}
			else
			{
				Market\Utils\Field::setChainValue($result, $fieldName, reset($enumDefaultIds), Market\Utils\Field::GLUE_BRACKET);
			}
		}

		return $result;
	}

	protected static function writeDatabase(TradingSetup\Model $setup, array $settings)
	{
		$reservedKeys = $setup->getReservedSettingsKeys();
		$settings = array_diff_key($settings, array_flip($reservedKeys));

		$updateResult = TradingSetup\Table::update($setup->getId(), [
			'SETTINGS' => static::convertSettingsToRows($settings),
		]);

		Market\Result\Facade::handleException($updateResult);
	}

	protected static function convertSettingsToRows(array $settings)
	{
		$result = [];

		foreach ($settings as $key => $value)
		{
			$result[] = [
				'NAME' => $key,
				'VALUE' => $value,
			];
		}

		return $result;
	}

	protected static function passValues(TradingSetup\Model $setup, array $settings)
	{
		$setup->wakeupService()->getOptions()->extendValues($settings);
	}
}