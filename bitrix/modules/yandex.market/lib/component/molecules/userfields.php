<?php
namespace Yandex\Market\Component\Molecules;

use Bitrix\Main;
use Yandex\Market\Reference\Concerns;
use Yandex\Market\Utils;
use Yandex\Market\Error;

class UserFields
{
	use Concerns\HasMessage;

	const FIELDS_ALL = 'all';
	const FIELDS_KNOWN = 'known';

	private $fieldsStrategy;
	private $fields = [];

	public function __construct($fieldsStrategy = self::FIELDS_ALL)
	{
		$this->fieldsStrategy = $fieldsStrategy;
	}

	public function know($name)
	{
		$this->fields[$name] = true;
	}

	protected function isOur($name)
	{
		return $this->fieldsStrategy === self::FIELDS_ALL || isset($this->fields[$name]);
	}

	public function sanitize(array $request, array $fields)
	{
		foreach ($fields as $fieldName => $userField)
		{
			if (!$this->isOur($fieldName)) { continue; }

			if (!empty($userField['SETTINGS']['READONLY']))
			{
				Utils\Field::unsetChainValue($request, $fieldName, Utils\Field::GLUE_BRACKET);
				continue;
			}

			$requested = Utils\Field::getChainValue($request, $fieldName, Utils\Field::GLUE_BRACKET);

			if ($userField['MULTIPLE'] === 'Y')
			{
				if ($requested === null) { continue; }

				$sanitized = [];
				$requested = is_array($requested) ? $requested : [];

				foreach ($requested as $requestValueItem)
				{
					$sanitizedValue = $this->sanitizeUserFieldValue($userField, $requestValueItem);

					if (!Utils\Value::isEmpty($sanitizedValue))
					{
						$sanitized[] = $sanitizedValue;
					}
				}
			}
			else
			{
				$sanitized = $this->sanitizeUserFieldValue($userField, $requested);
			}

			Utils\Field::setChainValue($request, $fieldName, $sanitized, Utils\Field::GLUE_BRACKET);
		}

		return $request;
	}

	protected function sanitizeUserFieldValue(array $userField, $value)
	{
		$result = $value;

		if (
			!empty($userField['USER_TYPE']['CLASS_NAME'])
			&& is_callable([$userField['USER_TYPE']['CLASS_NAME'], 'SanitizeFields'])
		)
		{
			$result = call_user_func(
				[$userField['USER_TYPE']['CLASS_NAME'], 'SanitizeFields'],
				$userField,
				$value
			);
		}

		return $result;
	}

	public function validate(Main\Entity\Result $result, $data, array $fields)
	{
		foreach ($fields as $fieldName => $userField)
		{
			if (!$this->isOur($fieldName)) { continue; }
			if (!empty($userField['SETTINGS']['READONLY']) || !empty($userField['DEPEND_HIDDEN'])) { continue; }
			if (!empty($userField['HIDDEN']) && $userField['HIDDEN'] !== 'N') { continue; }

			$dataField = Utils\Field::getChainValue($data, $fieldName, Utils\Field::GLUE_BRACKET);

			if ($userField['MULTIPLE'] === 'Y')
			{
				$values = is_array($dataField) ? $dataField : [];
			}
			else
			{
				$values = !Utils\Value::isEmpty($dataField) ? [ $dataField ] : [];
			}

			if (!empty($values))
			{
				foreach ($values as $value)
				{
					$checkResult = $this->checkUserFieldValue($fieldName, $userField, $value);

					if (!$checkResult->isSuccess())
					{
						$result->addErrors($checkResult->getErrors());
					}
				}
			}
			else if ($userField['MANDATORY'] === 'Y')
			{
				if (isset($userField['DEPRECATED']) && $userField['DEPRECATED'] === 'Y') { continue; }

				$result->addError(new Error\EntityError(
					self::getMessage('FIELD_REQUIRED', [
						'#FIELD_NAME#' => $userField['EDIT_FORM_LABEL'] ?: $fieldName,
					]),
					0,
					[ 'FIELD' => $fieldName ]
				));
			}
		}
	}

	protected function checkUserFieldValue($fieldName, $userField, $value)
	{
		$result = new Main\Entity\Result();

		if (!empty($userField['USER_TYPE']['CLASS_NAME']) && is_callable([$userField['USER_TYPE']['CLASS_NAME'], 'CheckFields']))
		{
			$userErrors = call_user_func(
				[$userField['USER_TYPE']['CLASS_NAME'], 'CheckFields'],
				$userField,
				$value
			);

			if (!empty($userErrors) && is_array($userErrors))
			{
				foreach ($userErrors as $userError)
				{
					$result->addError(new Error\EntityError(
						$userError['text'],
						0,
						[ 'FIELD' => $fieldName ]
					));
				}
			}
		}

		return $result;
	}

	public function beforeSave(array $fields, array $values, $primary = null, $originalValues = [])
	{
		$result = $values;

		foreach ($fields as $fieldName => $field)
		{
			if (!$this->isOur($fieldName)) { continue; }

			if (
				isset($field['USER_TYPE']['CLASS_NAME'])
				&& is_callable([$field['USER_TYPE']['CLASS_NAME'], 'onBeforeSave'])
			)
			{
				$userField = $field;
				$userField['ENTITY_VALUE_ID'] = $primary;
				$userField['VALUE'] = isset($originalValues[$fieldName]) ? $originalValues[$fieldName] : null;

				$fieldValue = Utils\Field::getChainValue($values, $fieldName, Utils\Field::GLUE_BRACKET);
				$fieldValue = call_user_func(
					[$field['USER_TYPE']['CLASS_NAME'], 'onBeforeSave'],
					$userField,
					$fieldValue
				);

				Utils\Field::setChainValue($result, $fieldName, $fieldValue, Utils\Field::GLUE_BRACKET);
			}
		}

		return $result;
	}
}