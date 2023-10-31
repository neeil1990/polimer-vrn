(function(BX, $, window) {

	var Plugin = BX.namespace('YandexMarket.Plugin');
	var Reference = BX.namespace('YandexMarket.Field.Reference');
	var Param = BX.namespace('YandexMarket.Field.Param');
	var Source = BX.namespace('YandexMarket.Source');
	var utils = BX.namespace('YandexMarket.Utils');

	var constructor = Param.Node = Reference.Base.extend({

		defaults: {
			type: null,
			valueType: 'string',
			required: false,
			copyType: null,
			linkedSources: [ 'iblock_property_feature' ],

			managerElement: '.js-param-manager',

			inputElement: '.js-param-node__input',
			sourceElement: '.js-param-node__source',
			fieldWrapElement: '.js-param-node__field-wrap',
			fieldElement: '.js-param-node__field',
			templateButtonElement: '.js-param-node__template-button',

			fieldTextTemplate: '<input class="b-param-table__input js-param-node__input js-param-node__field" type="text" />',
			fieldFormulaTemplate: '<div class="b-input-formula js-param-node__field-wrap" data-plugin="Ui.Input.Formula">' +
				'<select class="b-input-formula__function b-param-table__control js-param-node__input js-input-formula__function" data-complex="FUNCTION"></select>' +
				'<div class="b-input-formula__parts-wrap">' +
					'<select class="b-input-formula__parts b-param-table__input js-param-node__field js-param-node__input js-input-formula__parts" data-complex="PARTS" size="1"></select>' +
				'</div>' +
				'<button class="b-input-formula__dropdown b-param-table__control adm-btn js-input-formula__dropdown" type="button">...</button>' +
			'</div>',
			fieldSelectTemplate: '<select class="b-param-table__input js-param-node__input js-param-node__field" data-plugin="Ui.Input.TagInput" data-width="100%" data-tags="false"></select>',
			fieldTemplateTemplate: '<div class="b-control-group js-param-node__field-wrap" data-plugin="Ui.Input.Template">' +
			    '<input class="b-control-group__item pos--first b-param-table__input js-param-node__input js-param-node__field js-input-template__origin" type="text" />' +
			    '<button class="b-control-group__item pos--last width--by-content adm-btn around--control js-input-template__dropdown" type="button">...</button>' +
		    '</div>',

			langPrefix: 'YANDEX_MARKET_FIELD_PARAM_',
			lang: {}
		},

		initVars: function() {
			this.callParent('initVars', constructor);

			this._lastSource = null;
			this._fieldValueUserInput = null;
			this._manager = null;
		},

		initialize: function() {
			this.callParent('initialize', constructor);
			this.bind();
			this.initValueUi();
		},

		destroy: function() {
			this.unbind();
			this.callParent('destroy', constructor);
		},

		bind: function() {
			this.handleSourceChange(true);
			this.handleInputChange(true);
			this.handleLinkedSourceFieldChange(true);
		},

		unbind: function() {
			this.handleSourceChange(false);
			this.handleInputChange(false);
			this.handleLinkedSourceFieldChange(false);
		},

		handleSourceChange: function(dir) {
			var sourceElement = this.getElement('source');

			sourceElement[dir ? 'on' : 'off']('change keyup', $.proxy(this.onSourceChange, this));
		},

		handleParentField: function(field, dir) {
			this.handleCopyTypeFieldChange(field, dir);
			this.handleCopyTypeSelfFieldInput(dir);
		},

		handleCopyTypeFieldChange: function(parentField, dir) {
			var type = this.options.copyType;
			var typeCollection;
			var typeField;

			if (type != null) {
				typeCollection = parentField.getTypeCollection(type);

				typeCollection[dir ? 'on' : 'off']('change keyup', this.getElementSelector('field'), $.proxy(this.onCopyTypeFieldChange, this));
			}
		},

		handleCopyTypeSelfFieldInput: function(dir) {
			var type = this.options.copyType;

			if (type != null) {
				this.$el[dir ? 'on' : 'off']('input paste', this.getElementSelector('field'), $.proxy(this.onCopyTypeSelfFieldInput, this));
			}
		},

		handleLinkedSourceFieldChange: function(dir) {
			if (this.options.linkedSources == null && dir) { return; }

			this.$el[dir ? 'on' : 'off']('change', this.getElementSelector('field'), $.proxy(this.onLinkedSourceFieldChange, this));
		},

		handleInputChange: function(dir) {
			var inputSelector = this.getElementSelector('input');

			this.$el[dir ? 'on' : 'off']('change', inputSelector, $.proxy(this.onInputChange, this));
		},

		onSourceChange: function(evt) {
			var sourceElement = $(evt.target);
			var sourceValue = sourceElement.val();

			if (this._lastSource == null || this._lastSource !== sourceValue) {
				this._lastSource = sourceValue;
				this.refreshField(sourceValue);
			}
		},

		onCopyTypeFieldChange: function(evt) {
			var input = evt.currentTarget;

			this.copyFieldValue(input);
		},

		onCopyTypeSelfFieldInput: function(evt) {
			var input = evt.currentTarget;

			this._fieldValueUserInput = (input.value !== '');
		},

		onLinkedSourceFieldChange: function(evt) {
			const source = this.getElement('source').val();

			if (this.options.linkedSources.indexOf(source) === -1) { return; }

			this.$el.trigger('FieldParamNodeLinkedChange', {
				node: this,
				source: source,
				field: evt.target.value,
			});
		},

		onInputChange: function() {
			this.$el.trigger('FieldParamNodeChange');
		},

		preselect: function() {
			if (this.getElement('source').val() !== 'recommendation') { return; }

			const field = this.getElement('field');
			const options = field.find('option');

			if (field.data('type') !== 'select') { return; }

			for (let i = 0; i < options.length; ++i) {
				let option = options[i];

				if (option.value === '') { continue; }

				if (!option.selected) {
					option.selected = true;
					field.trigger('change');
				}

				break;
			}
		},

		syncLinked: function(source, field) {
			this.syncLinkedSource(source) && this.syncLinkedField(field);
		},

		syncLinkedSource: function(source) {
			const element = this.getElement('source');
			const options = element.find('option');
			let found = false;

			if (element.val() === source) { return true; }

			for (let i = 0; i < options.length; ++i) {
				let option = options[i];

				if (option.value !== source) { continue; }

				found = true;

				if (!option.selected) {
					option.selected = true;
					element.trigger('change'); // force reflow field
				}

				break;
			}

			return found;
		},

		syncLinkedField: function(value) {
			const field = this.getElement('field');
			const options = field.find('option');
			const marker = value.replace(/\.[^.]+$/, '.');
			let found = false;

			if (marker === '') { return found; }
			if (field.data('type') !== 'select') { return found; }

			for (let i = 0; i < options.length; ++i) {
				let option = options[i];

				if (option.value.indexOf(marker) !== 0) { continue; }

				found = true;

				if (!option.selected) {
					option.selected = true;
					field.trigger('change'); // force reflow select2
				}

				break;
			}

			return found;
		},

		clear: function() {
			this.callParent('clear', constructor);
			this._fieldValueUserInput = false;
		},

		setParentField: function(field) {
			var previousParent = this.getParentField();

			if (previousParent != null) {
				this.handleParentField(previousParent, false);
			}

			if (field != null) {
				this.handleParentField(field, true);
			}

			this.callParent('setParentField', [field], constructor);
		},

		isFieldValueUserInput: function(field) {
			var fieldElement;
			var fieldValue;

			if (this._fieldValueUserInput == null) {
				fieldElement = field || this.getElement('field');
				fieldValue = fieldElement.val();

				this._fieldValueUserInput = (fieldValue != null && fieldValue !== '');
			}

			return this._fieldValueUserInput;
		},

		copyFieldValue: function(fromElement) {
			var fieldElement = this.getElement('field');
			var fieldTagName = (fieldElement.prop('tagName') || '').toLowerCase();
			var fieldValue = fieldElement.val();
			var fromTagName = (fromElement.tagName || '').toLowerCase();
			var fromValue;
			var option;

			if (fieldTagName === 'input' && fromTagName === 'select' && !this.isFieldValueUserInput(fieldElement)) { // support copy only in input from select
				option = $('option', fromElement).filter(':selected');

				if (option.val()) { // is not placeholder
					fromValue = option.text();
				}

				if (fromValue != null) {
					fromValue = fromValue.replace(/^\[\d+\]/, '').trim(); // remove id

					fieldElement.val(fromValue);
				}
			}
		},

		refreshField: function(typeId) {
			var manager = this.getManager();
			var type = manager.getType(typeId);

			this.updateField(type, manager);
		},

		updateField: function(type, manager) {
			var fieldElement = this.getElement('field');
			var fieldEnumList;
			var fieldEnum;
			var fieldValue;
			var i;
			var fieldType = (fieldElement.data('type') || '').toLowerCase();
			var needType = type['CONTROL'] || 'select';
			var content;

			if (needType === 'select') {
				fieldEnumList = this.getFieldList(type['ID'], manager);
				fieldValue = fieldElement.val();
				content = '';

				if (fieldEnumList != null && fieldEnumList.length > 0) {
					if (!this.options.required) {
						content += '<option value="">' + this.getLang('SELECT_PLACEHOLDER') + '</option>';
					}

					for (i = 0; i < fieldEnumList.length; i++) {
						fieldEnum = fieldEnumList[i];

						if (fieldEnum['DEPRECATED'] && fieldValue !== fieldEnum['ID']) { continue; }

						content +=
							'<option ' +
								'value="' + fieldEnum['ID'] + '"' +
								(fieldValue === fieldEnum['ID'] ? ' selected' : '') +
							'>'
							+ utils.escape(fieldEnum['VALUE'])
							+ '</option>';
					}
				}
			}

			if (fieldType !== needType) {
				fieldElement = this.renderField(fieldElement, needType);
			}

			if (content != null) {
				fieldElement.html(content);
			}
		},

		renderField: function(field, type) {
			var templateKey = 'field' + type.substr(0, 1).toUpperCase() + type.substr(1);
			var template = this.getTemplate(templateKey);
			var fieldSelector = this.getElementSelector('field');
			var inputSelector = this.getElementSelector('input');
			var oldWrap = this.getElement('fieldWrap', field, 'closest');
			var newWrap = $(template);
			var newField = newWrap.filter(fieldSelector);
			var newFieldInputs = newField.add(newWrap.find(inputSelector));

			if (oldWrap.length === 0) { oldWrap = field; }
			if (newField.length === 0) { newField = newWrap.find(fieldSelector); }

			this.destroyValueUi(oldWrap);

			this.copyAttrList(field, newFieldInputs, ['name', 'data-name']);
			newField.data('type', type);

			newWrap.insertAfter(oldWrap);
			oldWrap.remove();

			this.initValueUi(newWrap);

			this._fieldValueUserInput = false;

			return newField;
		},

		destroyValueUi: function(newField) {
			var value = newField || this.getElementFieldWrap();

			Plugin.manager.destroyElement(value);
		},

		initValueUi: function(newField) {
			var value = newField || this.getElementFieldWrap();
			var plugins = Plugin.manager.initializeElement(value);
			var firstPlugin = plugins[0];

			if (firstPlugin != null) {
				firstPlugin.setOptions({
					sourceManager: this.getManager(),
					sourceType: this.getElement('source').val(),
					nodeType: this.options.type
				});

				if (typeof firstPlugin.render === 'function') {
					firstPlugin.render();
				}
			}
		},

		getElementField: function() {
			return this.getElement('field');
		},

		getElementFieldWrap: function() {
			var result = this.getElement('fieldWrap');

			return (result.length > 0 ? result : this.getElementField());
		},

		copyAttrList: function(fromElement, toElements, attrList) {
			var fromValues = this.readAttrList(fromElement, attrList);
			var toIndex;
			var toElement;

			for (toIndex = toElements.length - 1; toIndex >= 0; toIndex--) {
				toElement = toElements.eq(toIndex);
				this.writeAttrList(toElement, fromValues);
			}
		},

		readAttrList: function(field, attributeNames) {
			var complex = field.data('complex');
			var complexFull = complex ? '[' + complex + ']' : null;
			var complexPosition;
			var result = {};
			var attributeName;
			var attributeValue;
			var i;

			for (i = attributeNames.length - 1; i >= 0; i--) {
				attributeName = attributeNames[i];
				attributeValue = field.attr(attributeName);

				if (attributeValue == null) { continue; }

				if (attributeName.indexOf('name') !== -1) {
					attributeValue = attributeValue.replace(/\[]$/, '');

					if (complexFull) {
						complexPosition = attributeValue.indexOf(complexFull);

						if (complexPosition !== -1 && attributeValue.length === complexPosition + complexFull.length) {
							attributeValue = attributeValue.substring(0, complexPosition);

							if (attributeName === 'data-name') {
								attributeValue = attributeValue.replace(/^\[([^\]]+)]$/, '$1');
							}
						}
					}
				}

				result[attributeName] = attributeValue;
			}

			return result;
		},

		writeAttrList: function(field, attributeValues) {
			var complex = field.data('complex');
			var complexFull = complex ? '[' + complex + ']' : '';
			var attributeName;
			var attributeValue;

			for (attributeName in attributeValues) {
				if (!attributeValues.hasOwnProperty(attributeName)) { continue; }

				attributeValue = attributeValues[attributeName];

				if (complexFull && attributeName.indexOf('name') !== -1) {
					if (attributeName === 'data-name' && attributeValue.indexOf('[') !== 0) {
						attributeValue = '[' + attributeValue + ']' + complexFull;
					} else {
						attributeValue += complexFull;
					}
				}

				field.attr(attributeName, attributeValue);
			}
		},

		getFieldList: function(typeId, manager) {
			var result;

			manager = manager || this.getSourceManager();

			if (typeId === 'recommendation') {
				result = manager.getRecommendationList(this.options.type);
			} else {
				result = manager.getTypeFieldList(typeId, this.options.valueType, this.options.type);
			}

			return result;
		},

		getManager: function() {
			var element;

			if (this._manager == null) {
				element = this.getElement('manager', this.$el, 'closest');
				this._manager = Source.Manager.getInstance(element);
			}

			return this._manager;
		}

	}, {
		dataName: 'FieldParamNode'
	});

})(BX, jQuery, window);