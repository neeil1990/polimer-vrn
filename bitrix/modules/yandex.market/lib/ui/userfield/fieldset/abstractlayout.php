<?php

namespace Yandex\Market\Ui\UserField\Fieldset;

use Bitrix\Main;
use Yandex\Market\Ui\UserField;
use Yandex\Market\Data\TextString;

abstract class AbstractLayout
{
	const NAME_BASE = 'js-fieldset';

	protected $userField;
	protected $name;
	protected $fields;
	protected $fieldsetName;

	public function __construct($userField, $name, array $fields)
	{
		$this->userField = $userField;
		$this->name = $name;
		$this->fields = $fields;
		$this->fieldsetName = $this->hasParentFieldset()
			? static::NAME_BASE . '-' . Main\Security\Random::getString(3)
			: static::NAME_BASE;
	}

	abstract public function edit($value);

	abstract public function editMultiple($values);

	protected function extendFields($name, $fields)
	{
		foreach ($fields as $fieldKey => &$field)
		{
			$fieldName = $name;
			$fieldName .= $this->isComplexFieldName($fieldKey)
				? $this->makeChildFieldName($fieldKey)
				: sprintf('[%s]', $fieldKey);

			$field = UserField\Helper\Field::extend($field, $fieldName);

			if (!isset($field['SETTINGS'])) { $field['SETTINGS'] = []; }

			$field['SETTINGS']['PARENT_FIELDSET_NAME'] = $this->name;
			$field['SETTINGS']['PARENT_FIELDSET_BASE'] = $this->fieldsetName;
		}
		unset($field);

		return $fields;
	}

	protected function isComplexFieldName($fieldName)
	{
		return TextString::getPosition($fieldName, '[') !== false;
	}

	protected function makeChildFieldName($fieldName)
	{
		$bracketPosition = TextString::getPosition($fieldName, '[');

		if ($bracketPosition === false || $bracketPosition === 0)
		{
			$result = $fieldName;
		}
		else
		{
			$basePart = TextString::getSubstring($fieldName, 0, $bracketPosition);
			$leftPart = TextString::getSubstring($fieldName, $bracketPosition);

			$result = '[' . $basePart . ']';
			$result .= $leftPart;
		}

		return $result;
	}

	protected function hasParentFieldset()
	{
		return Helper::hasParentFieldset($this->userField);
	}

	protected function getParentFieldsetName($type)
	{
		return Helper::getParentFieldsetName($this->userField, $type);
	}

	protected function getFieldsetName($type)
	{
		return $this->fieldsetName . '-' . $type;
	}

	protected function getPluginAttributes($inputName)
	{
		if ($this->hasParentFieldset())
		{
			$selfName = Helper::makeRelativeName($this->userField, $inputName);

			$result = [
				'class' => $this->getParentFieldsetName('row__child'),
				'data-name' => $selfName,
				'data-element-namespace' => '.' . $this->fieldsetName,
			];
		}
		else
		{
			$result = [
				'class' => 'js-plugin',
				'data-base-name' => $inputName,
			];
		}

		return $result;
	}

	protected function resolveRowValues($values)
	{
		if (!is_array($values))
		{
			$values = [];
		}

		if (isset($this->userField['ROW']))
		{
			$values['PARENT_ROW'] = $this->userField['ROW'];
		}

		return $values;
	}

	protected function prepareFieldControl($control, $fieldKey, $field)
	{
		$attributes = [
			'class' => $this->getFieldsetName('row__input'),
		];
		$dataName = $this->isComplexFieldName($fieldKey)
			? $this->makeChildFieldName($fieldKey)
			: $fieldKey;

		$control = UserField\Helper\Attributes::insert($control, $attributes, static function($tagName, $existsAttributes) {
			return (
				!isset($existsAttributes['class'])
				|| TextString::getPosition($existsAttributes['class'], AbstractLayout::NAME_BASE) === false
			);
		});
		$control = UserField\Helper\Attributes::insertDataName($control, $dataName, $field['FIELD_NAME']);

		return $control;
	}
}