(function(BX, $, window) {

	var Plugin = BX.namespace('YandexMarket.Plugin');
	var Input = BX.namespace('YandexMarket.Ui.Input');
	var utils = BX.namespace('YandexMarket.Utils');

	var constructor = Input.Formula = Plugin.Base.extend({

		defaults: {
			sourceManager: null,
			sourceType: null,
			nodeType: null,

			functionElement: '.js-input-formula__function',
			partsElement: '.js-input-formula__parts',
			dropdownElement: '.js-input-formula__dropdown',
		},

		initialize: function() {
			this.callParent('initialize', constructor);
			this.bind();
			this.createPartsPlugin();
		},

		destroy: function() {
			this.destroyPartsPlugin();
			this.unbind();
			this.callParent('destroy', constructor);
		},

		bind: function() {
			this.handleFunctionChange(true);
			this.handleDropdownClick(true);
		},

		unbind: function() {
			this.handleFunctionChange(false);
			this.handleDropdownClick(false);
		},

		handleFunctionChange: function(dir) {
			var functionSelect = this.getElement('function');

			functionSelect[dir ? 'on' : 'off']('change', $.proxy(this.onFunctionChange, this));
		},

		handleDropdownClick: function(dir) {
			var dropdown = this.getElement('dropdown');

			dropdown[dir ? 'on' : 'off']('click', $.proxy(this.onDropdownClick, this));
		},

		onFunctionChange: function(evt) {
			this.syncFunctionAndPartsMultiple();
		},

		onDropdownClick: function(evt) {
			var button = evt.currentTarget;
			var isReady = ('OPENER' in button);

			if (!isReady) {
				this.openTemplatePopup(button);
			}
		},

		onSelectTemplateOption: function(fieldPath) {
			this.addSelectedField(fieldPath);
		},

		openTemplatePopup: function(button) {
			var options = this.getDropdownOptions();

			BX.adminShowMenu(button, options);
		},

		render: function() {
			if (!this.isFunctionsEmpty()) { return; } // already done

			this.fillFunctions();
			this.syncFunctionAndPartsMultiple();
 		},

		isFunctionsEmpty: function() {
			var functionSelect = this.getElement('function');

			return functionSelect.find('option').length === 0;
		},

		fillFunctions: function() {
			var manager = this.getSourceManager();
			var type = manager.getType(this.options.sourceType);
			var i;
			var functionOption;
			var content = '';

			if (type != null && type['FUNCTIONS'] != null) {
				for (i = 0; i < type['FUNCTIONS'].length; i++) {
					functionOption = type['FUNCTIONS'][i];

					content +=
						'<option value="' + functionOption['ID'] + '" data-multiple="' + (functionOption['MULTIPLE'] ? 'true' : 'false') + '">'
						+ utils.escape(functionOption['VALUE'])
						+ '</option>';
				}
			}

			this.getElement('function').html(content);
		},

		syncFunctionAndPartsMultiple: function() {
			var functionOptions = this.getElement('function').find('option');
			var selectedOption = functionOptions.filter(':selected');
			var functionMultiple;
			var partsSelect = this.getElement('parts');
			var partsPlugin;

			if (selectedOption.length === 0) { selectedOption = functionOptions.eq(0); }

			functionMultiple = !!selectedOption.data('multiple');

			if (functionMultiple !== partsSelect.prop('multiple'))  {
				partsPlugin = Input.TagInput.getInstance(partsSelect, true);
				partsSelect.prop('multiple', functionMultiple);
				this.updatePartsName(partsSelect, functionMultiple);
				partsPlugin.refreshPlugin();
			}
		},

		updatePartsName: function(partsSelect, isMultiple) {
			var attributeValue = partsSelect.attr('name');
			var hasMultiple;

			if (attributeValue == null) { return; }

			hasMultiple = /\[]$/.test(attributeValue);

			if (hasMultiple !== isMultiple) {
				if (hasMultiple) {
					attributeValue = attributeValue.replace(/\[]$/, '');
				} else {
					attributeValue += '[]';
				}

				partsSelect.attr('name', attributeValue);
			}
		},

		createPartsPlugin: function() {
			var partsElement = this.getElement('parts');
			var instance = Input.TagInput.getInstance(partsElement, true);

			if (instance == null) {
				new Input.TagInput(partsElement, {
					width: '100%',
					dataAdapter: $.fn.select2.amd.require('select2/data/search'),
					data: $.proxy(this.getPartsOptions, this),
				});
			}
		},

		destroyPartsPlugin: function() {
			var partsElement = this.getElement('parts');
			var instance = Input.TagInput.getInstance(partsElement, true);

			if (instance != null) {
				instance.destroy();
			}
		},

		addSelectedField: function(fieldPath) {
			var partsElement = this.getElement('parts');
			var partOption = this.getPartOption(fieldPath);
			var option = $('<option />');

			if (!partsElement.prop('multiple')) {
				partsElement.empty();
			}

			option.prop('selected', true);
			option.prop('value', partOption.id);
			option.text(partOption.text);

			partsElement.append(option);
			partsElement.trigger('change');
		},

		getPartOption: function(fieldPath) {
			var dotPosition = fieldPath.indexOf('.');
			var typeId = fieldPath.substr(0, dotPosition);
			var fieldId = fieldPath.substr(dotPosition + 1);
			var manager = this.getSourceManager();
			var type = manager.getType(typeId);
			var field = manager.getTypeField(typeId, fieldId);
			var result = {
				'id': fieldPath,
				'text': fieldPath,
			};

			if (type && field) {
				result['text'] = type['VALUE'] + ': ' + field['VALUE'];
			}

			return result;
		},

		getPartsOptions: function() {
			var manager = this.getSourceManager();
			var typeList = manager.getTypeList();
			var typeIndex;
			var type;
			var fieldList = manager.getFieldList();
			var fieldIndex;
			var field;
			var result = [];

			for (typeIndex = 0; typeIndex < typeList.length; typeIndex++) {
				type = typeList[typeIndex];

				for (fieldIndex = 0; fieldIndex < fieldList.length; fieldIndex++) {
					field = fieldList[fieldIndex];

					if (field['SOURCE'] === type['ID']) {
						result.push({
							'id': type['ID'] + '.' + field['ID'],
							'text': type['VALUE'] + ': ' + field['VALUE'],
						});
					}
				}
			}

			return result;
		},

		getDropdownOptions: function() {
			var manager = this.getSourceManager();
			var typeList = manager.getTypeList();
			var typeIndex;
			var type;
			var typeOptions;
			var fieldList = manager.getFieldList();
			var fieldIndex;
			var field;
			var recommendationList;
			var result = [];

			for (typeIndex = 0; typeIndex < typeList.length; typeIndex++) {
				type = typeList[typeIndex];
				typeOptions = [];

				if (type['VARIABLE'] || type['TEMPLATE']) {
					// nothing
				} else if (type['ID'] === 'recommendation') {
					recommendationList = manager.getRecommendationList(this.options.nodeType);

					if (recommendationList !== null) {
						for (fieldIndex = 0; fieldIndex < recommendationList.length; fieldIndex++) {
							field = recommendationList[fieldIndex];

							typeOptions.push({
								'TEXT': field['VALUE'],
								'ONCLICK': $.proxy(this.onSelectTemplateOption, this, field['ID'].replace('|', '.'))
							});
						}
					}
				} else {
					for (fieldIndex = 0; fieldIndex < fieldList.length; fieldIndex++) {
						field = fieldList[fieldIndex];

						if (field['SOURCE'] === type['ID']) {
							typeOptions.push({
								'TEXT': field['VALUE'],
								'ONCLICK': $.proxy(this.onSelectTemplateOption, this, type['ID'] + '.' + field['ID'])
							});
						}
					}
				}

				if (typeOptions.length > 0) {
					result.push({
						'TEXT': type['VALUE'],
						'MENU': typeOptions
					});
				}
			}

			return result;
		},

		getSourceManager: function() {
			return this.options.sourceManager;
		}

	}, {
		dataName: 'uiInputFormula'
	});

})(BX, jQuery, window);