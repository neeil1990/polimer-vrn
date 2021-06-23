(function(BX, $, window) {

	var Plugin = BX.namespace('YandexMarket.Plugin');
	var Input = BX.namespace('YandexMarket.Ui.Input');
	var Source = BX.namespace('YandexMarket.Source');

	var constructor = Input.Template = Plugin.Base.extend({

		defaults: {
			sourceManager: null,
			sourceType: null,
			nodeType: null,

			originElement: '.js-input-template__origin',
			dropdownElement: '.js-input-template__dropdown',
		},

		initialize: function() {
			this.callParent('initialize', constructor);
			this.bind();
		},

		destroy: function() {
			this.unbind();
			this.callParent('destroy', constructor);
		},

		bind: function() {
			this.handleDropdownClick(true);
		},

		unbind: function() {
			this.handleDropdownClick(false);
		},

		handleDropdownClick: function(dir) {
			var dropdown = this.getElement('dropdown');

			dropdown[dir ? 'on' : 'off']('click', $.proxy(this.onDropdownClick, this));
		},

		onDropdownClick: function(evt) {
			var button = evt.currentTarget;
			var isReady = ('OPENER' in button);

			if (!isReady) {
				this.openTemplatePopup(button);
			}
		},

		onSelectTemplateOption: function(fieldPath) {
			this.insertFieldText('{=' + fieldPath + '}');
		},

		openTemplatePopup: function(button) {
			var options = this.getDropdownOptions();

			BX.adminShowMenu(button, options);
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

		insertFieldText: function(text) {
			var field = this.getElement('origin');
			var node = field[0];
			var value = node.value;
			var partBefore;
			var partAfter;
			var endIndex;
			var range;

			field.focus();

			if (typeof node.selectionStart !== 'undefined' && typeof node.selectionEnd !== 'undefined') {
				endIndex = node.selectionEnd;
				partBefore = value.slice(0, node.selectionStart);
				partAfter = value.slice(endIndex);
				node.value = (partBefore.length > 0 ? partBefore + ' ' : '') + text + (partAfter.length > 0 ? ' ' + partAfter : '');
				node.selectionStart = node.selectionEnd = endIndex + text.length + (partBefore.length > 0 ? 1 : 0);
			} else {
				partBefore = '' + node.value;
				node.value = (partBefore.length > 0 ? partBefore + ' ' : '') + text;
			}

			field.trigger('change');
			field.focus();
		},

		getSourceManager: function() {
			return this.options.sourceManager;
		}

	}, {
		dataName: 'uiInputTemplate'
	});

})(BX, jQuery, window);