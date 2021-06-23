<?php

namespace Yandex\Market\Ui\UserField\Fieldset;

use Bitrix\Main;
use Yandex\Market;
use Yandex\Market\Ui\UserField;

class TableLayout extends AbstractLayout
{
	use Market\Reference\Concerns\HasLang;
	use Market\Reference\Concerns\HasOnceStatic;

	protected static function includeMessages()
	{
		Main\Localization\Loc::loadMessages(__FILE__);
	}

	public function edit($value)
	{
		static::onceStatic('loadRowAssets');

		$attributes = $this->getPluginAttributes($this->name);

		$result = '<table>';
		$result .= $this->editRow($this->name, $value, $attributes);
		$result .= '</table>';

		return $result;
	}

	public function editMultiple($values)
	{
		static::onceStatic('loadCollectionAssets');
		static::onceStatic('loadRowAssets');

		$valueIndex = 0;
		$inputName = preg_replace('/\[]$/', '', $this->name);
		$onlyPlaceholder = false;

		if (empty($values))
		{
			$onlyPlaceholder = true;
			$values[] = [];
		}

		$collectionAttributes = $this->getPluginAttributes($inputName) + [
			'data-plugin' => 'Field.Fieldset.Collection',
		];

		if ($this->userField['MANDATORY'] === 'Y')
		{
			$collectionAttributes['data-persistent'] = 'true';
		}

		$result = sprintf('<table %s>', UserField\Helper\Attributes::stringify($collectionAttributes));

		if ($this->useTableHeader())
		{
			$result .= $this->getTableHeader($onlyPlaceholder);
		}

		foreach ($values as $value)
		{
			$valueName = $inputName . '[' . $valueIndex . ']';
			$rowAttributes = [
				'class' => $this->getFieldsetName('collection__item') . ($onlyPlaceholder ? ' is--hidden' : ''),
			];
			$rowHtml = $this->editRow($valueName, $value, $rowAttributes, true);

			if ($onlyPlaceholder)
			{
				$rowHtml = UserField\Helper\Attributes::sliceInputName($rowHtml);
			}

			$result .= $rowHtml;

			++$valueIndex;
		}

		$result .= '</table>';
		$result .= '<input ' . UserField\Helper\Attributes::stringify([
			'class' => 'adm-btn ' . $this->getFieldsetName('collection__item-add'),
			'type' => 'button',
			'value' => static::getLang('USER_FIELD_FIELDSET_ADD'),
		]) . ' />';

		return $result;
	}

	protected static function loadCollectionAssets()
	{
		Market\Ui\Assets::loadPluginCore();
		Market\Ui\Assets::loadFieldsCore();
		Market\Ui\Assets::loadPlugins([
			'Field.Fieldset.Collection',
		]);
	}

	protected static function loadRowAssets()
	{
		Market\Ui\Assets::loadPluginCore();
		Market\Ui\Assets::loadFieldsCore();
		Market\Ui\Assets::loadPlugins([
			'Field.Fieldset.Summary',
			'Field.Fieldset.Row',
		]);
	}

	protected function useTableHeader()
	{
		return !empty($this->userField['SETTINGS']['USE_HEADER']);
	}

	protected function getTableHeader($isHidden = false)
	{
		$result = sprintf('<thead %s><tr>', UserField\Helper\Attributes::stringify([
			'class' => $this->getFieldsetName('collection__header') . ' ' . ($isHidden ? 'is--hidden' : ''),
		]));

		foreach ($this->fields as $field)
		{
			$fieldHeader = isset($field['SETTINGS']['TABLE_HEADER'])
				? $field['SETTINGS']['TABLE_HEADER']
				: $field['NAME'];

			$result .= sprintf('<td>%s</td>', $fieldHeader);
		}

		$result .= '</tr></thead>';

		return $result;
	}

	protected function editRow($name, $values, array $attributes = [], $allowDelete = false)
	{
		$fields = $this->extendFields($name, $this->fields);
		$result = sprintf('<tr %s>', UserField\Helper\Attributes::stringify($attributes + array_filter([
			'data-plugin' => 'Field.Fieldset.Row',
			'data-element-namespace' => $this->hasParentFieldset() ? '.' . $this->fieldsetName : null,
		])));

		foreach ($fields as $fieldKey => $field)
		{
			$value = Market\Utils\Field::getChainValue($values, $fieldKey, Market\Utils\Field::GLUE_BRACKET);

			$row = UserField\Helper\Renderer::getEditRow($field, $value, $values);
			$control = $this->prepareFieldControl($row['CONTROL'], $fieldKey, $field);

			// write result

			$result .= sprintf('<td>%s</td>', $control);
		}

		if ($allowDelete)
		{
			$result .= sprintf(
				'<td><a class="b-remove %s" href="#" title="%s"></a></td>',
				$this->getFieldsetName('collection__item-delete'),
				static::getLang('USER_FIELD_FIELDSET_DELETE')
			);
		}

		$result .= '</tr>';

		return $result;
	}
}