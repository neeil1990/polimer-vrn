(function(BX, window) {

	var constructor;
	var Plugin = BX.namespace('YandexMarket.Plugin');
	var Input = BX.namespace('YandexMarket.Ui.Input');

	Input.TradingOutlet = constructor = Plugin.Base.extend({

		defaults: {
			refreshUrl: null,
			service: null,
			parentElement: null,
			inputElement: 'select',
			optionElement: 'option',

			usedSign: null,
			usedKeys: '',

			lang: {},
			langPrefix: 'YANDEX_MARKET_USER_FIELD_TRADING_OUTLET_'
		},

		initVars: function() {
			this._usedSign = null;
		},

		initialize: function() {
			this.bind();
			this.initialLoad();
		},

		destroy: function() {
			this.unbind();
		},

		bind: function() {
			this.handleClick(true);
		},

		unbind: function() {
			this.handleClick(false);
		},

		handleClick: function(dir) {
			this.$el[dir ? 'on' : 'off']('click', $.proxy(this.onClick, this));
		},

		onClick: function() {
			this.activate();
		},

		initialLoad: function() {
			var fetcher = this.getFetcher();

			if (fetcher.hasEnum()) {
				this.render(fetcher.getEnum());
				this.setUsedSign(fetcher.getSign());
			} else if (this.isUsedChanged()) {
				this.activate();
			}
		},

		activate: function() {
			var formData = this.getFormData();
			var usedSign = this.makeUsedSign(formData);

			this.load(formData, usedSign).then(
				$.proxy(this.activateEnd, this, usedSign),
				$.proxy(this.activateStop, this)
			);
		},

		activateStop: function(message) {
			this.showError(message);
		},

		activateEnd: function(sign, values) {
			this.setUsedSign(sign);
			this.render(values, true);
		},

		load: function(formData, sign) {
			var url = this.options.refreshUrl;

			formData.push({
				name: 'service',
				value: this.options.service
			});

			return this.getFetcher().load(url, formData, sign);
		},

		getForm: function() {
			var result = this.$el.closest('form');
			var field;
			var parentField;

			if (result.length === 0) {
				field = this.searchParentField();
				parentField = field ? field.getParentField() : null;

				if (parentField != null) {
					result = parentField.$el.closest('form');
				}
			}

			return result;
		},

		getFormData: function() {
			var form = this.getForm();
			var field = this.searchParentField();
			var fieldData;
			var result = form.serializeArray();

			if (field != null) {
				fieldData = field.$el.find('input, select, textarea').serializeArray();
				result = result.concat(fieldData);
			}

			return result;
		},

		searchParentField: function() {
			var node = this.$el.closest('[data-plugin^="Field."]');
			var pluginName = node.data('plugin');

			return pluginName ? Plugin.manager.getInstance(node) : null;
		},

		searchParentForm: function() {
			var node = this.$el.closest('[data-plugin^="Field."]');
			var pluginName = node.data('plugin');
			var field;
			var parentField;
			var result;

			if (pluginName) {
				field = Plugin.manager.getInstance(node);
				parentField = field.getParentField();

				if (parentField) {
					result = parentField.$el.closest('form');
				}
			}

			return result;
		},

		showError: function(message) {
			var formattedMessage = this.getLang('REFRESH_FAIL', {
				'MESSAGE': message
			});

			alert(formattedMessage);
		},

		isUsedChanged: function() {
			return this.fetchUsedSign() !== this.getUsedSign();
		},

		fetchUsedSign: function() {
			var form = this.getForm();
			var keys = this.getUsedKeys();
			var keyIndex;
			var key;
			var node;
			var value;
			var parts = [];

			for (keyIndex = 0; keyIndex < keys.length; keyIndex++) {
				key = keys[keyIndex];
				node = form.prop(key);
				value = '';

				if (node instanceof HTMLElement) {
					value = node.value;
				}

				parts.push(key + '=' + value);
			}

			return parts.join('|');
		},

		makeUsedSign: function(formData) {
			var keys = this.getUsedKeys();
			var keyIndex;
			var key;
			var values = this.getFormDataValues(formData, keys);
			var value;
			var parts = [];

			for (keyIndex = 0; keyIndex < keys.length; keyIndex++) {
				key = keys[keyIndex];
				value = values[key] || '';

				parts.push(key + '=' + value);
			}

			return parts.join('|');
		},

		getFormDataValues: function(formData, keys) {
			var formValue;
			var formValueIndex;
			var result = {};

			for (formValueIndex = 0; formValueIndex < formData.length; formValueIndex++) {
				formValue = formData[formValueIndex];

				if (keys.indexOf(formValue.name) !== -1) {
					result[formValue.name] = formValue.value;
				}
			}

			return result;
		},

		getUsedSign: function() {
			return this._usedSign != null ? this._usedSign : this.options.usedSign;
		},

		setUsedSign: function(sign) {
			this._usedSign = sign;
		},

		getUsedKeys: function() {
			return this.options.usedKeys.split('|');
		},

		render: function(values, allowChange) {
			var inputs = this.getInputs();
			var inputIndex;
			var input;
			var allowInputChange;

			for (inputIndex = 0; inputIndex < inputs.length; inputIndex++) {
				input = inputs.eq(inputIndex);
				allowInputChange = (allowChange && inputIndex === 0);

				if (input.is('select')) {
					this.renderSelect(input, values, allowInputChange);
				}
			}
		},

		renderSelect: function(select, values, allowChange) {
			var optionList = this.getElement('option', select);
			var optionIndex;
			var option;
			var valueIndex;
			var value;
			var existValueIds = [];

			// create new

			for (valueIndex = 0; valueIndex < values.length; valueIndex++) {
				value = values[valueIndex];
				option = this.searchOption(optionList, value.ID);

				if (option == null) {
					option = this.createOption(select, value);
				} else if (option.textContent !== value.VALUE) {
					option.textContent = value.VALUE;
				}

				if (allowChange && valueIndex === 0 && this.isEmptyValue(select.val())) {
					this.selectOption(select, option);
				}

				existValueIds.push(value.ID);
			}

			// delete non-exists

			for (optionIndex = 0; optionIndex < optionList.length; optionIndex++) {
				option = optionList[optionIndex];

				if (existValueIds.indexOf(option.value) === -1 && !this.isEmptyValue(option.value)) {
					option.parentElement.removeChild(option);
				}
			}
		},

		searchOption: function(optionList, valueId) {
			var option;
			var optionValue;
			var i;
			var result = null;

			valueId = ('' + valueId);

			for (i = 0; i < optionList.length; i++) {
				option = optionList[i];
				optionValue = ('' + option.value);

				if (optionValue === valueId) {
					result = option;
					break;
				}
			}

			return result;
		},

		createOption: function(select, value) {
			var option = document.createElement('option');

			option.value = value.ID;
			option.textContent = value.VALUE;

			select.append(option);

			return option;
		},

		selectOption: function(select, option) {
			option.selected = true;
		},

		isEmptyValue: function(value) {
			return !value;
		},

		getInputs: function() {
			var parentSelector = this.getElementSelector('parent');
			var parent = parentSelector != null
				? this.getElement('parent', this.$el, 'closest')
				: this.$el.parent();

			return this.getElement('input', parent);
		},

		getFetcher: function() {
			return Input.TradingOutletFetcher.getInstance(document);
		}

	}, {
		dataName: 'uiInputTradingOutlet'
	});

})(BX, window);