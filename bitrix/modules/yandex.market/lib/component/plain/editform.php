<?php

namespace Yandex\Market\Component\Plain;

use Yandex\Market;
use Bitrix\Main;

abstract class EditForm extends Market\Component\Base\EditForm
{
	protected $fields;
	protected $userFields;

	public function __construct(\CBitrixComponent $component)
	{
		parent::__construct($component);

		$this->userFields = new Market\Component\Molecules\UserFields();
	}

	public function prepareComponentParams($params)
	{
		$params['FIELDS'] = $this->prepareFields($params['FIELDS']);
		$params['TABS'] = $this->prepareTabs($params['TABS'], $params['FIELDS']);

		return $params;
	}

	protected function prepareFields($fields)
	{
		$fields = $this->extendFields($fields);
		$fields = $this->sortFields($fields);

		return $fields;
	}

	protected function extendFields($fields)
	{
		$result = [];

		foreach ($fields as $name => $field)
		{
			$userField = $field;

			if (!isset($field['USER_TYPE']) && isset($field['TYPE']))
			{
				$userField['USER_TYPE'] = Market\Ui\UserField\Manager::getUserType($field['TYPE']);
			}

			if (
				isset($userField['HIDDEN'], $userField['USER_TYPE']['CLASS_NAME'])
				&& $userField['HIDDEN'] === 'H'
				&& method_exists($userField['USER_TYPE']['CLASS_NAME'], 'GetList')
			)
			{
				$className = $userField['USER_TYPE']['CLASS_NAME'];
				$values = $className::GetList($userField);
				$values = Market\Ui\UserField\Helper\Enum::toArray($values);

				$userField['HIDDEN'] = empty($values) ? 'Y' : 'N';
			}

			$userField += [
				'TAB' => 'COMMON',
				'MULTIPLE' => 'N',
				'EDIT_IN_LIST' => 'Y',
				'EDIT_FORM_LABEL' => $field['NAME'],
				'FIELD_NAME' => $name,
				'SETTINGS' => [],
			];

			$result[$name] = $userField;
		}

		return $result;
	}

	protected function sortFields($fields)
	{
		$fieldsWithSort = array_filter($fields, static function($tab) { return isset($tab['SORT']); });

		if (count($fieldsWithSort) > 0)
		{
			uasort($fields, static function($fieldA, $fieldB) {
				$sortA = isset($fieldA['SORT']) ? $fieldA['SORT'] : 5000;
				$sortB = isset($fieldB['SORT']) ? $fieldB['SORT'] : 5000;

				if ($sortA === $sortB) { return 0; }

				return $sortA < $sortB ? -1 : 1;
			});
		}

		return $fields;
	}

	protected function prepareTabs($tabs, $fields)
	{
		$tabs = $this->extendTabs($tabs, $fields);
		$tabs = $this->sortTabs($tabs);

		return $tabs;
	}

	/** @noinspection SlowArrayOperationsInLoopInspection */
	protected function extendTabs($tabs, $fields)
	{
		$result = [];
		$usedFields = [];

		foreach ($tabs as $tabKey => $tab)
		{
			// fields

			if (!isset($tab['fields']))
			{
				$tabCode = !is_numeric($tabKey) ? $tabKey : 'COMMON';
				$tabFields = $this->getFieldCodesForTab($fields, $tabCode);

				$tab['fields'] = array_diff($tabFields, $usedFields);
			}

			$usedFields = array_merge($usedFields, $tab['fields']);

			// export

			$result[] = $tab;
		}

		return $result;
	}

	protected function sortTabs($tabs)
	{
		$tabsWithSort = array_filter($tabs, static function($tab) { return isset($tab['sort']); });

		if (count($tabsWithSort) > 0)
		{
			uasort($tabs, static function($tabA, $tabB) {
				$sortA = isset($tabA['sort']) ? $tabA['sort'] : 5000;
				$sortB = isset($tabB['sort']) ? $tabB['sort'] : 5000;

				if ($sortA === $sortB) { return 0; }

				return $sortA < $sortB ? -1 : 1;
			});
		}

		return $tabs;
	}

	protected function getFieldCodesForTab($fields, $tabCode)
	{
		$result = [];

		foreach ($fields as $fieldCode => $field)
		{
			$fieldTab = isset($field['TAB']) ? $field['TAB'] : 'COMMON';

			if ($fieldTab === $tabCode)
			{
				$result[] = $fieldCode;
			}
		}

		return $result;
	}

	public function modifyRequest($request, $fields)
	{
		return $this->userFields->sanitize($request, $fields);
	}

	public function extend($data, array $select = [])
	{
		$result = $this->restoreDefaultsForHiddenFields($data, $select);

		return $result;
	}

	protected function restoreDefaultsForHiddenFields($data, array $select)
	{
		$fields = $this->getComponentResult('FIELDS');
		$result = $data;

		if (empty($select))
		{
			$select = array_keys($fields);
		}

		foreach ($select as $fieldName)
		{
			if (!isset($fields[$fieldName])) { continue; }

			$field = $fields[$fieldName];

			if (!empty($field['DEPEND_HIDDEN']) && isset($field['SETTINGS']['DEFAULT_VALUE']))
			{
				$fieldValue = array_key_exists($fieldName, $data) ? $data[$fieldName] : $field['VALUE'];

				if ($fieldValue === false)
				{
					$result[$fieldName] = $field['SETTINGS']['DEFAULT_VALUE'];
				}
			}
		}

		return $result;
	}

	public function validate($data, array $fields = null)
	{
		$result = new Main\Entity\Result();

		if ($fields === null) { return $result; }

		$this->userFields->validate($result, $data, $fields);

		return $result;
	}

	protected function sliceFieldsDependHidden($fields, $values)
	{
		$result = $values;

		foreach ($fields as $fieldName => $field)
		{
			if (empty($field['DEPEND_HIDDEN'])) { continue; }

			Market\Utils\Field::unsetChainValue($result, $fieldName, Market\Utils\Field::GLUE_BRACKET);
		}

		return $result;
	}

	protected function applyUserFieldsOnBeforeSave($fields, $values)
	{
		return $this->userFields->beforeSave(
			$fields,
			$values,
			$this->getComponentParam('PRIMARY') ?: null,
			array_map(function(array $field) { return $this->component->getOriginalValue($field); }, $fields)
		);
	}

	public function getFields(array $select = [], $item = null)
	{
		$result = $this->getAllFields();
		$result = $this->applyFieldsSelect($result, $select);
		$result = $this->applyFieldsDeprecated($result, $item);

		return $result;
	}

	protected function applyFieldsSelect(array $fields, array $select)
	{
		if (empty($select)) { return $fields; }

		$selectMap = array_flip($select);

		return array_intersect_key($fields, $selectMap);
	}

	protected function applyFieldsDeprecated(array $fields, $item = null)
	{
		if ($this->needShowDeprecated()) { return $fields; }

		$nextOverrides = [];

		foreach ($fields as &$field)
		{
			if (!empty($nextOverrides))
			{
				$field += $nextOverrides;
				$nextOverrides = [];
			}

			if (!isset($field['DEPRECATED']) || $field['DEPRECATED'] !== 'Y') { continue; }

			$value = $item !== null
				? Market\Utils\Field::getChainValue($item, $field['FIELD_NAME'], Market\Utils\Field::GLUE_BRACKET)
				: null;

			if (empty($value))
			{
				$field['HIDDEN'] = 'Y';
				$nextOverrides += array_intersect_key($field, [
					'INTRO' => true,
				]);
			}
		}
		unset($field);

		return $fields;
	}

	protected function needShowDeprecated()
	{
		$request = Main\Application::getInstance()->getContext()->getRequest();

		return $request->get('deprecated') === 'Y';
	}

	protected function getAllFields()
	{
		return (array)$this->getComponentParam('FIELDS');
	}

	public function getRequiredParams()
	{
		return [
			'FIELDS',
		];
	}
}