<?php

namespace Yandex\Market\Component\TradingSettings;

use Yandex\Market;
use Bitrix\Main;

class EditForm extends Market\Component\Plain\EditForm
{
	public function processPostAction($action, $data)
	{
		switch ($action)
		{
			case 'reset':
				$this->processResetAction($data);
			break;

			default:
				parent::processPostAction($action, $data);
			break;
		}
	}

	protected function processResetAction($data)
	{
		if (!$this->getComponentParam('ALLOW_SAVE'))
		{
			$message = $this->getComponentLang('SAVE_DISALLOW');
			throw new Main\AccessDeniedException($message);
		}

		Market\Trading\Setup\Table::update($data['PRIMARY'], [ 'SETTINGS' => [] ]);
	}

	public function load($primary, array $select = [], $isCopy = false)
	{
		$result = $this->loadSetupSettings($primary, $select);

		if (empty($result))
		{
			$result = $this->loadFieldsDefaults($select);
		}
		else
		{
			$result += $this->fillFieldsValueEmpty($select);
		}

		return $result;
	}

	protected function loadSetupSettings($primary, array $select = [])
	{
		try
		{
			$setup = Market\Trading\Setup\Model::loadById($primary);
			$settings = $setup->getSettings()->getValues();
			$result = !empty($settings)
				? $setup->wakeupService()->getOptions()->getValues()
				: [];
		}
		catch (Main\ObjectNotFoundException $exception)
		{
			$result = [];
		}

		return $result;
	}

	protected function loadFieldsDefaults(array $select = [])
	{
		$result = [];

		foreach ($this->getFields($select) as $fieldName => $field)
		{
			if (!isset($field['SETTINGS']['DEFAULT_VALUE'])) { continue; }

			Market\Utils\Field::setChainValue($result, $fieldName, $field['SETTINGS']['DEFAULT_VALUE'], Market\Utils\Field::GLUE_BRACKET);
		}

		return $result;
	}

	protected function fillFieldsValueEmpty(array $select = [])
	{
		$result = [];

		foreach ($this->getFields($select) as $fieldName => $field)
		{
			if (!empty($field['SETTINGS']['READONLY'])) { continue; }

			$isHidden = isset($field['HIDDEN']) && $field['HIDDEN'] === 'Y';
			$hasDefaultValue = isset($field['SETTINGS']['DEFAULT_VALUE']);
			$value = ($isHidden && $hasDefaultValue)
				? $field['SETTINGS']['DEFAULT_VALUE']
				: false;

			Market\Utils\Field::setChainValue($result, $fieldName, $value, Market\Utils\Field::GLUE_BRACKET);
		}

		return $result;
	}

	public function add($fields)
	{
		throw new Main\NotSupportedException();
	}

	public function update($primary, $values)
	{
		if (!empty($values))
		{
			$fields = $this->getComponentResult('FIELDS');

			$values = $this->applyUserFieldsOnBeforeSave($fields, $values);
			$values = $this->sliceFieldsDependHidden($fields, $values);

			$rows = $this->convertValuesToRows($values);
		}
		else
		{
			$rows = [];
		}

		return Market\Trading\Setup\Table::update($primary, [ 'SETTINGS' => $rows ]);
	}

	protected function convertRowsToValues($rows)
	{
		$result = [];

		foreach ($rows as $row)
		{
			$result[$row['NAME']] = $row['VALUE'];
		}

		return $result;
	}

	protected function convertValuesToRows($values)
	{
		$result = [];

		foreach ($values as $key => $value)
		{
			$result[] = [
				'NAME' => $key,
				'VALUE' => $value,
			];
		}

		return $result;
	}
}