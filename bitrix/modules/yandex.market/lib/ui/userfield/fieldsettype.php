<?php

namespace Yandex\Market\Ui\UserField;

use Yandex\Market;
use Bitrix\Main;

class FieldsetType
{
	/** @noinspection PhpUnused */
	public static function sanitizeFields($userField, $value)
	{
		if (!is_array($value)) { return null; }

		return static::mapValues($userField, $value, static function($field, $name, $value) {
			$sanitizedValue = static::sanitizeUserFieldValue($field, $value);

			if (Market\Utils\Value::isEmpty($sanitizedValue)) { return null; }

			return $sanitizedValue;
		});
	}

	protected static function sanitizeUserFieldValue($field, $value)
	{
		$result = $value;

		if (
			!empty($field['USER_TYPE']['CLASS_NAME'])
			&& is_callable([$field['USER_TYPE']['CLASS_NAME'], 'SanitizeFields'])
		)
		{
			$result = call_user_func(
				[$field['USER_TYPE']['CLASS_NAME'], 'SanitizeFields'],
				$field,
				$value
			);
		}

		return $result;
	}

	public static function GetAdminListViewHTML($userField, $htmlControl)
	{
		$value = Helper\ComplexValue::asSingle($userField, $htmlControl);

		return static::renderSummary($userField, $value);
	}

	public static function GetAdminListViewHTMLMulty($userField, $htmlControl)
	{
		$parts = [];

		foreach (Helper\ComplexValue::asMultiple($userField, $htmlControl) as $value)
		{
			$parts[] = static::renderSummary($userField, $value);
		}

		return implode(', ', $parts);
	}

	protected static function renderSummary($userField, $value)
	{
		$fields = static::getFields($userField);
		$summaryTemplate = isset($userField['SETTINGS']['SUMMARY']) ? $userField['SETTINGS']['SUMMARY'] : null;

		return !empty($value)
			? (string)Helper\Summary::make($fields, $value, $summaryTemplate)
			: '';
	}

	public static function GetEditFormHtml($userField, $htmlControl)
	{
		$values = Helper\ComplexValue::asSingle($userField, $htmlControl);
		$layout = static::makeLayout($userField, $htmlControl);

		return $layout->edit($values);
	}

	public static function GetEditFormHtmlMulty($userField, $htmlControl)
	{
		$values = Helper\ComplexValue::asMultiple($userField, $htmlControl);
		$layout = static::makeLayout($userField, $htmlControl);

		return $layout->editMultiple($values);
	}

	protected static function getFields($userField)
	{
		return isset($userField['FIELDS'])
			? (array)$userField['FIELDS']
			: [];
	}

	protected static function makeLayout($userField, $htmlControl)
	{
		$fields = static::getFields($userField);
		$layout = !empty($userField['SETTINGS']['LAYOUT']) ? $userField['SETTINGS']['LAYOUT'] : 'table';

		if ($layout === 'summary')
		{
			$result = new Fieldset\SummaryLayout($userField, $htmlControl['NAME'], $fields);
		}
		else
		{
			$result = new Fieldset\TableLayout($userField, $htmlControl['NAME'], $fields);
		}

		return $result;
	}

	/** @noinspection PhpUnused */
	public static function ymExportValue(array $userField, $value, array $row = null)
	{
		if (!is_array($value)) { return null; }

		$result = [];
		$mapped = static::mapValues($userField, $value, static function($field, $name, $value, array $row) {
			$field = Helper\Field::extend($field, $name);
			$exportValue = static::exportUserFieldValue($field, $value, $row);

			if (Market\Utils\Value::isEmpty($exportValue)) { return null; }

			return $exportValue;
		});

		foreach (static::getFields($userField) as $name => $field)
		{
			$value = Market\Utils\Field::getChainValue($mapped, $name, Market\Utils\Field::GLUE_BRACKET);

			if ($value === null) { continue; }

			$result[] = [
				'code' => $name,
				'name' => html_entity_decode(strip_tags($field['NAME'])),
				'value' => $value,
			];
		}

		return $result;
	}

	protected static function exportUserFieldValue($field, $value, array $row)
	{
		if (
			!empty($field['USER_TYPE']['CLASS_NAME'])
			&& is_callable([$field['USER_TYPE']['CLASS_NAME'], 'ymExportValue'])
		)
		{
			$result = call_user_func(
				[$field['USER_TYPE']['CLASS_NAME'], 'ymExportValue'],
				$field,
				$value,
				$row
			);
		}
		else
		{
			$result = $value;
		}

		return $result;
	}

	protected static function mapValues($userField, array $values, callable $function)
	{
		$result = [];

		foreach (static::getFields($userField) as $name => $field)
		{
			if (isset($field['DEPEND']) && !Market\Utils\UserField\DependField::test($field['DEPEND'], $values)) { continue; }

			$fieldValue = Market\Utils\Field::getChainValue($values, $name, Market\Utils\Field::GLUE_BRACKET);

			if ($field['MULTIPLE'] === 'Y')
			{
				$mappedValues = [];
				$fieldValue = is_array($fieldValue) ? $fieldValue : [];

				foreach ($fieldValue as $fieldValueItem)
				{
					$mappedValue = $function($field, $name, $fieldValueItem, $values);

					if ($mappedValue === null) { continue; }

					$mappedValues[] = $mappedValue;
				}

				if (empty($mappedValues)) { continue; }

				Market\Utils\Field::setChainValue($result, $name, $mappedValues, Market\Utils\Field::GLUE_BRACKET);
			}
			else
			{
				$mappedValue = $function($field, $name, $fieldValue, $values);

				if ($mappedValue === null) { continue; }

				Market\Utils\Field::setChainValue($result, $name, $mappedValue, Market\Utils\Field::GLUE_BRACKET);
			}
		}

		return $result;
	}
}