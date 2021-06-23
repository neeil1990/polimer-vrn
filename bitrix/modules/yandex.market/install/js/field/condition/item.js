(function(BX, $, window) {

	'use strict';

	var Plugin = BX.namespace('YandexMarket.Plugin');
	var Reference = BX.namespace('YandexMarket.Field.Reference');
	var Condition = BX.namespace('YandexMarket.Field.Condition');
	var Source = BX.namespace('YandexMarket.Source');
	var Ui = BX.namespace('YandexMarket.Ui');
	var utils = BX.namespace('YandexMarket.Utils');

	var constructor = Condition.Item = Reference.Base.extend({

		defaults: {
			inputElement: '.js-condition-item__input',
			inputHolderElement: '.js-condition-item__input-holder',

			fieldElement: '.js-condition-item__field',
			compareElement: '.js-condition-item__compare',
			valueCellElement: '.js-condition-item__value-cell',
			valueElement: '.js-condition-item__value',

			managerElement: '.js-condition-manager',

			valueHiddenTemplate: '<input class="js-condition-item__input js-condition-item__value" type="hidden" />',
			valueInputTemplate: '<input class="b-filter-condition-field__input adm-input js-condition-item__input js-condition-item__value" type="text" />',
			valueSelectTemplate: '<select class="b-filter-condition-field__input js-condition-item__input js-condition-item__value js-plugin" size="1" data-plugin="Ui.Input.FilterInput"></select>',
			valueAutocompleteTemplate: '<select class="b-filter-condition-field__input js-condition-item__input js-condition-item__value js-plugin" size="1" data-plugin="Ui.Input.FilterInput" data-autocomplete="true" data-type="autocomplete"></select>',
			valueDateTemplate: '<select class="b-filter-condition-field__input js-condition-item__input js-condition-item__value js-plugin" size="1" data-plugin="Ui.Input.FilterDate"></select>',
			valueDateTimeTemplate: '<select class="b-filter-condition-field__input js-condition-item__input js-condition-item__value js-plugin" size="1" data-plugin="Ui.Input.FilterDate" data-time="true"></select>',

			lang: {},
			langPrefix: 'YANDEX_MARKET_FIELD_CONDITION_'
		},

		initVars: function() {
			this.callParent('initVars', [constructor]);
			this._lastField = null;
			this._lastCompare = null;
		},

		initialize: function() {
			this.callParent('initialize', [constructor]);
			this.bind();
		},

		destroy: function() {
			this.unbind();
			this.callParent('destroy', [constructor]);
		},

		bind: function() {
			this.handleFieldChange(true);
			this.handleCompareChange(true);
		},

		unbind: function() {
			this.handleFieldChange(false);
			this.handleCompareChange(false);
		},

		handleFieldChange: function(dir) {
			var field = this.getElement('field');

			field.on('change keyup', $.proxy(this.onFieldChange, this));
		},

		handleCompareChange: function(dir) {
			var compare = this.getElement('compare');

			compare.on('change keyup', $.proxy(this.onCompareChange, this));
		},

		onFieldChange: function(evt) {
			var field = $(evt.target);
			var option = field.find('option').filter(':selected');
			var fieldValue = option.val();
			var type;
			var compareValue;

			if (this._lastField == null || this._lastField !== fieldValue) {
				this._lastField = fieldValue;

				type = option.data('type');
				compareValue = this.refreshCompare(type);

				this._lastCompare = compareValue;

				this.refreshValue(fieldValue, compareValue);
			}
		},

		onCompareChange: function(evt) {
			var compare = $(evt.target);
			var option = compare.find('option').filter(':selected');
			var fieldValue;
			var compareValue = option.val();

			if (this._lastCompare == null || this._lastCompare !== compareValue) {
				this._lastCompare = compareValue;

				fieldValue = this.getElement('field').val();

				this.refreshValue(fieldValue, compareValue);
			}
		},

		updateName: function() {
			var baseName = this.getBaseName();

			this.callParent('updateName', [constructor]);

			this.getElement('inputHolder').each(function(index, element) {
				element.name = baseName + '[' + element.getAttribute('data-name') + ']';
			});
		},

		unsetName: function() {
			this.callParent('unsetName', [constructor]);

			this.getElement('inputHolder').removeAttr('name');
		},

		getDisplayValue: function() {
			var result = this.callParent('getDisplayValue', constructor);
			var valueElement = this.getElement('value');

			result['VALUE_HIDDEN'] = ((valueElement.prop('type') || '').toLowerCase() === 'hidden');

			return result;
		},

		isValid: function() {
			var valueList = this.getValue();
			var result = true;

			if (
				this.isEmptyValue(valueList['FIELD'])
				|| this.isEmptyValue(valueList['COMPARE'])
				|| this.isEmptyValue(valueList['VALUE'])
			) {
				result = false;
			}

			return result;
		},

		isEmptyValue: function(value) {
			var result = false;
			var valueString;

			if (value == null) {
				result = true;
			} else if ($.isArray(value)) {
				result = (value.length === 0);
			} else {
				valueString = ('' + value).trim();
				result = (valueString === '');
			}

			return result;
		},

		refreshCompare: function(fieldType) {
			var manager = this.getManager();
			var compareSelect = this.getElement('compare');
			var optionList = compareSelect.find('option');
			var option;
			var compareValue;
			var compareData;
			var optionTypesAttribute;
			var optionTypes;
			var i;
			var isActive;
			var firstActiveOption;
			var needResetSelected = false;
			var result;

			for (i = optionList.length - 1; i >= 0; i--) {
				option = optionList.eq(i);
				compareValue = option.val();
				compareData = manager.getCompare(compareValue);
				isActive = (!fieldType || !compareData || compareData['TYPE_LIST'].indexOf(fieldType) !== -1);

				option.prop('disabled', !isActive);

				if (isActive) {
					firstActiveOption = option;

					if (option.prop('selected')) {
						result = compareValue;
					}
				} else {
					option.prop('selected') && (needResetSelected = true);
				}
			}

			if (needResetSelected && firstActiveOption) {
				firstActiveOption.prop('selected', true);
				result = firstActiveOption.val();
			} else if (result == null && firstActiveOption) {
				result = firstActiveOption.val();
			}

			return result;
		},

		refreshValue: function(fieldValue, compareValue) {
			var manager = this.getManager();
			var isMultiple = this.isValueMultiple(compareValue, manager);
			var definedValue = this.getValueDefined(compareValue, manager);
			var isAutocomplete = false;
			var valueType;
			var valueList;
			var valueElement;

			if (definedValue == null) {
				valueList = this.getValueList(fieldValue, compareValue, manager);
				isAutocomplete = this.isValueAutocomplete(fieldValue, null, manager);
				valueType = this.getValueType(fieldValue, null, manager);
			}

			valueElement = this.updateValue(valueList, isMultiple, definedValue, isAutocomplete, valueType);

			this.updateValueState(valueElement, fieldValue, compareValue, this.getIblockId(manager));
		},

		updateValue: function(enumList, isMultiple, definedValue, isAutocomplete, dataType) {
			var valueCell = this.getElement('valueCell');
			var valueElement = this.getElement('value');
			var enumItem;
			var i;
			var valueTagName = (valueElement.data('type') || valueElement.prop('tagName') || '').toLowerCase();
			var needTagName;
			var content;

			if (
				valueTagName === 'input'
				&& (valueElement.prop('type') || '').toLowerCase() === 'hidden'
			) {
				valueTagName = 'hidden';
			}

			if (definedValue != null) {
				needTagName = 'hidden';
			} else if (dataType === 'DATE') {
				needTagName = 'date';
				content = this.compileEnumOptions(enumList);
			} else if (dataType === 'DATETIME') {
				needTagName = 'dateTime';
				content = this.compileEnumOptions(enumList);
			} else if (enumList && enumList.length > 0) {
				needTagName = 'select';
				content = this.compileEnumOptions(enumList);
			} else if (isAutocomplete) {
				needTagName = 'autocomplete';
				content = '';
			} else if (isMultiple) {
				needTagName = 'select';
				content = '';
			} else {
				needTagName = 'input';
			}

			if (valueTagName !== needTagName || isMultiple !== valueElement.prop('multiple')) {
				valueElement = this.renderValue(valueElement, needTagName, isMultiple);
			}

			if (content != null) {
				valueElement.html(content);
				valueElement.triggerHandler('uiRefresh');
			}

			if (definedValue != null) {
				valueElement.val(definedValue);
				valueCell.addClass('visible--hidden');
			} else {
				valueCell.removeClass('visible--hidden');
			}

			return valueElement;
		},

		compileEnumOptions: function(list) {
			var content;
			var i;
			var item;

			if (list) {
				content = '';

				for (i = 0; i < list.length; i++) {
					item = list[i];
					content += '<option value="' + item['ID'] + '">' + utils.escape(item['VALUE']) + '</option>';
				}
			}

			return content;
		},

		updateValueState: function(element, fieldValue, compareValue, iblockId) {
			element.data('sourceField', fieldValue);
			element.data('sourceCompare', compareValue);
			element.data('iblockId', iblockId);
			element.attr('data-source-field', fieldValue);
			element.attr('data-source-compare', compareValue);
			element.attr('data-iblock-id', iblockId);
		},

		renderValue: function(element, tagName, isMultiple) {
			var templateKey = 'value' + tagName.substr(0, 1).toUpperCase() + tagName.substr(1);
			var template = this.getTemplate(templateKey);
			var newField = $(template);

			this.destroyValueUi(element);

			newField.prop('multiple', !!isMultiple);

			this.copyAttrList(element, newField, ['name', 'data-name']);

			newField.insertAfter(element);
			element.remove();

			this.initValueUi(newField);

			return newField;
		},

		destroyValueUi: function(newField) {
			var value = newField || this.getElement('value');

			Plugin.manager.destroyContext(value);
		},

		initValueUi: function(newField) {
			var value = newField || this.getElement('value');

			Plugin.manager.initializeContext(value);
		},

		copyAttrList: function(fromElement, toElement, attrList) {
			var attrName;
			var attrValue;
			var i;

			for (i = 0; i < attrList.length; i++) {
				attrName = attrList[i];
				attrValue = fromElement.attr(attrName);

				if (attrName === 'name' && fromElement.prop('multiple') != toElement.prop('multiple')) {
					if (!toElement.prop('multiple')) {
						attrValue = attrValue.replace(/\[\]$/, '');
					} else if (/\[\]$/.test(attrValue) === false) {
						attrValue += '[]';
					}
				}

				if (attrValue != null) {
					toElement.attr(attrName, attrValue);
				}
			}
		},

		getValueDefined: function(compareValue, manager) {
			var compareData;

			if (manager == null) { manager = this.getManager(); }

			compareData = manager.getCompare(compareValue);

			return (compareData && 'DEFINED' in compareData ? compareData['DEFINED'] : null);
		},

		isValueMultiple: function(compareValue, manager) {
			var compareData;

			if (manager == null) { manager = this.getManager(); }

			compareData = manager.getCompare(compareValue);

			return (compareData && compareData['MULTIPLE']);
		},

		isValueAutocomplete: function(typeId, fieldId, manager) {
			var field;

			if (manager == null) { manager = this.getManager(); }

			field = manager.getTypeField(typeId, fieldId);

			return field && field['AUTOCOMPLETE'];
		},

		getValueType: function(typeId, fieldId, manager) {
			var field;

			if (manager == null) { manager = this.getManager(); }

			field = manager.getTypeField(typeId, fieldId);

			return field && field['TYPE'];
		},

		getValueList: function(fieldValue, compareValue, manager) {
			var compareData;
			var result;

			if (manager == null) { manager = this.getManager(); }

			compareData = manager.getCompare(compareValue);

			if (compareData && 'ENUM' in compareData) {
				result = compareData['ENUM'];
			} else {
				result = manager.getEnumList(fieldValue);
			}

			return result;
		},

		getIblockId: function(manager) {
			if (manager == null) { manager = this.getManager(); }

			return manager.getIblockId();
		},

		getManager: function() {
			var parent = this.getParentField();
			var managerElement;
			var result;

			if (parent) {
				result = parent.getManager();
			} else {
				managerElement = this.getElement('manager', this.$el, 'closest');
				result = Source.Manager.getInstance(managerElement);
			}

			return result;
		}

	}, {
		dataName: 'FieldConditionItem'
	});

})(BX, jQuery, window);