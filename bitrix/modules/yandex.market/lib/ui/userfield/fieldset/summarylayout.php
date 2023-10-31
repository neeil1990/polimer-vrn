<?php

namespace Yandex\Market\Ui\UserField\Fieldset;

use Bitrix\Main;
use Yandex\Market;
use Yandex\Market\Ui\UserField;

class SummaryLayout extends AbstractLayout
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

		$pluginAttributes = $this->getPluginAttributes($this->name);

		return $this->editRow($this->name, $value, $pluginAttributes);
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
			'data-plugin' => 'Field.Fieldset.SummaryCollection',
		];

		$result = sprintf('<div %s>', UserField\Helper\Attributes::stringify($collectionAttributes));

		foreach ($values as $value)
		{
			$valueName = $inputName . '[' . $valueIndex . ']';
			$rowHtml = $this->editRow($valueName, $value, [
				'class' => $this->getFieldsetName('collection__item') . ($onlyPlaceholder ? ' is--hidden' : ''),
			]);

			if ($onlyPlaceholder)
			{
				$rowHtml = UserField\Helper\Attributes::sliceInputName($rowHtml);
			}

			$result .= $rowHtml;

			++$valueIndex;
		}

		$result .= '<div class="b-field-add">';
		$result .= '<input ' . UserField\Helper\Attributes::stringify([
			'class' => 'adm-btn ' . $this->getFieldsetName('collection__item-add'),
			'type' => 'button',
			'value' => static::getLang('USER_FIELD_FIELDSET_ADD'),
		]) . ' />';
		$result .= '</div>';
		$result .= '</div>';

		return $result;
	}

	protected static function loadCollectionAssets()
	{
		Market\Ui\Assets::loadPluginCore();
		Market\Ui\Assets::loadFieldsCore();
		Market\Ui\Assets::loadPlugins([
			'Field.Fieldset.Collection',
			'Field.Fieldset.SummaryCollection',
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

	protected function editRow($name, $value, array $attributes = [])
	{
		$value = $this->resolveRowValues($value);
		$fields = $this->extendFields($name, $this->fields);
		$summaryTemplate = isset($this->userField['SETTINGS']['SUMMARY']) ? $this->userField['SETTINGS']['SUMMARY'] : null;
		$summary = !empty($value)
			? (string)UserField\Helper\Summary::make($fields, $value, $summaryTemplate)
			: '';
		$placeholder = isset($this->userField['SETTINGS']['PLACEHOLDER'])
			? $this->userField['SETTINGS']['PLACEHOLDER']
			: static::getLang('USER_FIELD_FIELDSET_SUMMARY_HOLDER');
		$useCollection = (isset($attributes['class']) && Market\Data\TextString::getPosition($attributes['class'], $this->getFieldsetName('collection__item')) !== false);

		$rootAttributes =
			$attributes
			+ array_filter([
				'data-plugin' => 'Field.Fieldset.Summary',
				'data-lang' => array_filter([
					'MODAL_TITLE' => $this->userField['NAME'],
				]),
				'data-summary' => $summaryTemplate,
				'data-modal-width' => isset($this->userField['SETTINGS']['MODAL_WIDTH']) ? $this->userField['SETTINGS']['MODAL_WIDTH'] : null,
				'data-modal-height' => isset($this->userField['SETTINGS']['MODAL_HEIGHT']) ? $this->userField['SETTINGS']['MODAL_HEIGHT'] : null,
				'data-lang-placeholder' => $placeholder,
			])
			+ $this->collectFieldsSummaryAttributes($fields);

		$rootAttributes['class'] = 'b-form-pill' . (isset($rootAttributes['class']) ? ' ' . $rootAttributes['class'] : '');

		$result = '<div ' . UserField\Helper\Attributes::stringify($rootAttributes) . '>';
		$result .= sprintf('<a class="b-link action--heading target--inside %s" href="#">', $this->getFieldsetName('summary__text'));
		$result .= $summary ?: $placeholder;
		$result .= '</a>';
		$result .= sprintf('<button class="b-close %s" type="button" title=""></button>', $useCollection ? $this->getFieldsetName('collection__item-delete') : $this->getFieldsetName('summary__clear'));
		$result .= sprintf('<div class="is--hidden %s">', $this->getFieldsetName('summary__edit-modal'));
		$result .= $this->renderEditForm($fields, $value);
		$result .= '</div>';
		$result .= '</div>';

		return $result;
	}

	protected function collectFieldsSummaryAttributes($fields)
	{
		$result = [];

		foreach ($fields as $code => $field)
		{
			if (isset($field['SETTINGS']['SUMMARY']) && is_string($field['SETTINGS']['SUMMARY']))
			{
				$attributeName = 'data-field-' . Market\Data\TextString::toLower($code) . '-summary';

				$result[$attributeName] = $field['SETTINGS']['SUMMARY'];
			}

			if (!empty($field['SETTINGS']['UNIT']))
			{
				$attributeName = 'data-field-' . Market\Data\TextString::toLower($code) . '-unit';

				$result[$attributeName] = is_array($field['SETTINGS']['UNIT'])
					? implode('|', $field['SETTINGS']['UNIT'])
					: $field['SETTINGS']['UNIT'];
			}
		}

		return $result;
	}

	protected function renderEditForm($fields, $values)
	{
		$activeGroup = null;
		$groupHtml = '';
		$hasGroupFields = false;
		$visibleCount = 0;

		$result = sprintf('<table %s>', UserField\Helper\Attributes::stringify(array_filter([
			'class' => 'edit-table ' . $this->getFieldsetName('summary__field'),
			'width' => '100%',
			'data-plugin' => 'Field.Fieldset.Row',
			'data-element-namespace' => $this->hasParentFieldset() ? '.' . $this->fieldsetName : null,
		])));

		foreach ($fields as $fieldKey => $field)
		{
			if (!empty($field['HIDDEN']) && $field['HIDDEN'] !== 'N') { continue; }

			$value = Market\Utils\Field::getChainValue($values, $fieldKey, Market\Utils\Field::GLUE_BRACKET);

			$row = UserField\Helper\Renderer::getEditRow($field, $value, $values);

			// write result

			if (isset($field['GROUP']) && $field['GROUP'] !== $activeGroup)
			{
				if ($activeGroup !== null)
				{
					$result .= sprintf(
						'<tr class="heading %s"><td colspan="2">%s</td></tr>',
						$hasGroupFields ? '' : 'is--hidden',
						$activeGroup
					);
				}

				$result .= $groupHtml;
				$groupHtml = '';
				$activeGroup = $field['GROUP'];
				$hasGroupFields = false;
			}

			// row attributes

			$rowAttributes = [];

			if ($row['ROW_CLASS'] !== '')
			{
				$rowAttributes['class'] = $row['ROW_CLASS'];
			}

			if (isset($field['DEPEND']))
			{
				Market\Ui\Assets::loadPlugin('Ui.Input.DependField');

				$rowAttributes['class'] =
					(isset($rowAttributes['class']) ? $rowAttributes['class'] . ' ' : '')
					. 'js-plugin-delayed';
				$rowAttributes['data-plugin'] = 'Ui.Input.DependField';
				$rowAttributes['data-depend'] = Market\Utils::jsonEncode($field['DEPEND'], JSON_UNESCAPED_UNICODE);
				$rowAttributes['data-form-element'] = '.' . $this->getFieldsetName('summary__field');

				if (!Market\Utils\UserField\DependField::test($field['DEPEND'], $values))
				{
					$rowAttributes['class'] .= ' is--hidden';
				}
				else
				{
					$hasGroupFields = true;
				}
			}
			else
			{
				$hasGroupFields = true;
			}

			// title cell

			$titleAttributes = [
				'class' => 'adm-detail-content-cell-l',
				'valign' => $row['VALIGN'] ?: 'middle',
				'width' => '40%',
			];

			if (!empty($field['SETTINGS']['VALIGN']))
			{
				$titleAttributes['valign'] = $field['SETTINGS']['VALIGN'];
			}

			if ($titleAttributes['valign'] === 'top')
			{
				if (!empty($field['SETTINGS']['VALIGN_PUSH']))
				{
					$pushTitle = $field['SETTINGS']['VALIGN_PUSH'] === true ? 'top' : $field['SETTINGS']['VALIGN_PUSH'];
				}
				else
				{
					$controlCount = (
						mb_substr_count($row['CONTROL'], ' type="text"')
						+ mb_substr_count($row['CONTROL'], ' type="number"')
						+ mb_substr_count($row['CONTROL'], '<select')
						+ mb_substr_count($row['CONTROL'], '<textarea')
					);

					$pushTitle = ($controlCount === 1) ? 'top' : null;
				}

				$titleAttributes['class'] .= $pushTitle ? ' push--' . $pushTitle : '';
			}

			// control

			$control = $this->prepareFieldControl($row['CONTROL'], $fieldKey, $field);
			$control = UserField\Helper\Attributes::delayPluginInitialization($control);

			$titleCell = $field['NAME'];

			if (!empty($field['HELP_MESSAGE']))
			{
				if (isset($field['HELP_POSITION']))
				{
					$helpPosition = 'b-tag-tooltip--content_' . $field['HELP_POSITION'];
				}
				else
				{
					$helpPosition = $visibleCount > 5 ? 'b-tag-tooltip--content_bottom' : '';
				}

				$titleHelp = sprintf(
					'<span class="b-icon icon--question indent--right b-tag-tooltip--holder">'
					. '<span class="b-tag-tooltip--content b-tag-tooltip--content_right %s">%s</span>'
					. '</span>',
					$helpPosition,
					$field['HELP_MESSAGE']
				);

				$titleCell = $titleHelp . $titleCell;
			}

			$groupHtml .= $this->renderFieldPrologRow($field);

			/** @noinspection HtmlUnknownAttribute */
			$groupHtml .= sprintf(
				'<tr %s>'
				. '<td %s>%s</td>'
				. '<td class="adm-detail-content-cell-r" width="60%%">%s</td>'
				. '</tr>',
				UserField\Helper\Attributes::stringify($rowAttributes),
				UserField\Helper\Attributes::stringify($titleAttributes),
				$titleCell,
				$control
			);

			$groupHtml .= $this->renderFieldEpilogRow($field);

			if (!isset($rowAttributes['class']) || Market\Data\TextString::getPosition($rowAttributes['class'], 'is--hidden') === false)
			{
				++$visibleCount;
			}
		}

		if ($activeGroup !== null)
		{
			$result .= sprintf(
				'<tr class="heading %s"><td colspan="2">%s</td></tr>',
				$hasGroupFields ? '' : 'is--hidden',
				$activeGroup
			);
		}

		$result .= $groupHtml;
		$result .= '</table>';

		return $result;
	}

	protected function renderFieldPrologRow($field)
	{
		if (!isset($field['INTRO'])) { return ''; }

		return sprintf(
			'<tr>'
			. '<td class="adm-detail-content-cell-l" width="40%%">&nbsp;</td>'
			. '<td class="adm-detail-content-cell-r" width="60%%"><small>%s</small></td>'
			. '</tr>',
			$field['INTRO']
		);
	}

	protected function renderFieldEpilogRow($field)
	{
		$contents = '';

		if (isset($field['DESCRIPTION']))
		{
			$contents .= sprintf('<small>%s</small>', $field['DESCRIPTION']);
		}

		if (isset($field['NOTE']))
		{
			$contents .= '<div class="b-admin-message-list compensate--spacing">';
			$contents .= BeginNote();
			$contents .= $field['NOTE'];
			$contents .= EndNote();
			$contents .= '</div>';
		}

		if ($contents === '') { return ''; }

		return sprintf(
			'<tr>'
			. '<td class="adm-detail-content-cell-l pos-inner--top" width="40%%">&nbsp;</td>'
			. '<td class="adm-detail-content-cell-r pos-inner--top" width="60%%">%s</td>'
			. '</tr>',
			$contents
		);
	}
}