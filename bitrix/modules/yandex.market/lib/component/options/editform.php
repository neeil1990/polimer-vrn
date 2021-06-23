<?php

namespace Yandex\Market\Component\Options;

use Yandex\Market;
use Bitrix\Main;

class EditForm extends Market\Component\Plain\EditForm
{
	public function load($primary, array $select = [], $isCopy = false)
	{
		$result = [];

		foreach ($this->getFields($select) as $name => $field)
		{
			$value = $this->getOptionValue($name);

			if ($value === null)
			{
				$value = $this->getDefaultValue($field);
			}

			$result[$name] = $this->convertOptionToFieldValue($field, $value);
		}

		return $result;
	}

	public function getFields(array $select = [], $item = null)
	{
		$result = parent::getFields($select, $item);

		if (in_array('PERMISSIONS', $select, true))
		{
			$result += $this->extendFields([
				'PERMISSIONS' => [
					'TYPE' => 'string',
					'NAME' => 'PERMISSIONS'
				],
			]);
		}

		return $result;
	}

	public function add($fields)
	{
		throw new Main\NotSupportedException();
	}

	public function update($primary, $fields)
	{
		$this->saveOptions($fields);
		$this->savePermissions();

		return new Main\Entity\UpdateResult();
	}

	protected function saveOptions($values)
	{
		foreach ($values as $name => $value)
		{
			$field = $this->getField($name);

			if ($field === null) { continue; }

			$optionValue = $this->convertFieldToOptionValue($field, $value);
			$optionValueString = (string)$optionValue;

			if ($optionValueString === '' || (string)$this->getDefaultValue($field) === $optionValueString)
			{
				Market\Config::removeOption($name);
			}
			else
			{
				Market\Config::setOption($name, $optionValue);
			}
		}
	}

	protected function savePermissions()
	{
		global $APPLICATION;
		global $USER;
		global $GROUPS;
		global $RIGHTS;
		global $SITES;
		global $REQUEST_METHOD;

		ob_start();
		$module_id = Market\Config::getModuleName();
		$Update = '1'; // need inside main module
		require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/admin/group_rights.php';
		echo '</table>';
		ob_end_clean();
	}

	protected function getOptionValue($name)
	{
		$option = (string)Market\Config::getOption($name);
		$result = null;

		if ($option !== '')
		{
			$result = $option;
		}

		return $result;
	}

	protected function getDefaultValue($field)
	{
		$result = null;

		if (isset($field['SETTINGS']['DEFAULT_VALUE']))
		{
			$result = $field['SETTINGS']['DEFAULT_VALUE'];
		}

		return $result;
	}

	protected function convertFieldToOptionValue($field, $value)
	{
		$result = $value;
		$valueString = (string)$value;

		if ($valueString !== '' && $field['TYPE'] === 'boolean')
		{
			$result = ($valueString === '1' ? 'Y' : 'N');
		}

		return $result;
	}

	protected function convertOptionToFieldValue($field, $value)
	{
		$result = $value;

		if ($value !== null && $field['TYPE'] === 'boolean')
		{
			$result = ($value === 'Y' ? '1' : '0');
		}

		return $result;
	}

	protected function getField($name)
	{
		$fields = $this->getAllFields();

		return isset($fields[$name]) ? $fields[$name] : null;
	}
}