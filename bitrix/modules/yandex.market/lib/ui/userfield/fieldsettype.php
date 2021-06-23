<?php

namespace Yandex\Market\Ui\UserField;

use Yandex\Market;
use Bitrix\Main;

class FieldsetType
{
	public static function sanitizeFields($userField, $value)
	{
		if (!is_array($value)) { return null; }

		$result = [];

		foreach (static::getFields($userField) as $name => $field)
		{
			if (isset($field['DEPEND']) && !Market\Utils\UserField\DependField::test($field['DEPEND'], $value)) { continue; }

			$fieldValue = Market\Utils\Field::getChainValue($value, $name, Market\Utils\Field::GLUE_BRACKET);

			if ($field['MULTIPLE'] === 'Y')
			{
				$sanitizedValues = [];
				$fieldValue = is_array($fieldValue) ? $fieldValue : [];

				foreach ($fieldValue as $fieldValueItem)
				{
					$sanitizedValue = static::sanitizeUserFieldValue($field, $fieldValueItem);

					if (!Market\Utils\Value::isEmpty($sanitizedValue))
					{
						$sanitizedValues[] = $fieldValueItem;
					}
				}

				if (!empty($sanitizedValues))
				{
					Market\Utils\Field::setChainValue($result, $name, $sanitizedValues, Market\Utils\Field::GLUE_BRACKET);
				}
			}
			else
			{
				Market\Utils\Field::setChainValue($result, $name, $fieldValue, Market\Utils\Field::GLUE_BRACKET);
			}
		}

		return $result;
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
}