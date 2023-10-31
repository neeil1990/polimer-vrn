(function(BX, window) {

	var Plugin = BX.namespace('YandexMarket.Plugin');
	var Input = BX.namespace('YandexMarket.Ui.Input');

	const constructor = Input.TradingOutlet = Input.Autocomplete.extend({

		defaults: {
			service: null,
			usedKeys: '',
			paging: true,
		},

		getAjaxOptions: function() {
			const options = this.callParent('getAjaxOptions', constructor);

			options.templateSelection = $.proxy(this.formatSelection, this);
			options.templateResult = $.proxy(this.formatSuggest, this);
			options.ajax.transport = $.proxy(this.ajaxTransport, this);

			return options;
		},

		ajaxTransport: function(params, success, failure) {
			const fetcher = this.getFetcher();
			const sign = this.makeSign(params.data);
			let promise;

			if (fetcher.has(sign)) {
				promise = $.when(fetcher.get(sign));
			} else {
				promise = fetcher.load(params.url, params.data, sign);
			}

			promise.then(success, failure);
		},

		makeAjaxData: function(params) {
			const formData = this.getFormData();
			const usedKeys = this.getUsedKeys();
			const result = $.extend(
				this.callParent('makeAjaxData', [params], constructor),
				this.getFormDataValues(formData, usedKeys),
				{ service: this.options.service }
			);

			delete result['q'];

			return result;
		},

		formatSelection: function(option) {
			return option.id;
		},

		formatSuggest: function(option) {
			return option.id != null ? '[' + option.id + '] ' + option.text : option.text;
		},

		getForm: function() {
			let result = this.$el.closest('form');
			let field;
			let parentField;

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
			const form = this.getForm();
			const field = this.searchParentField();
			let fieldData;
			let result = form.serializeArray();

			if (field != null) {
				fieldData = field.$el.find('input, select, textarea').serializeArray();
				result = result.concat(fieldData);
			}

			return result;
		},

		searchParentField: function() {
			const node = this.$el.closest('[data-plugin^="Field."]');
			const pluginName = node.data('plugin');

			return pluginName ? Plugin.manager.getInstance(node) : null;
		},

		getFormDataValues: function(formData, keys) {
			const result = {};
			let formValue;
			let formValueIndex;

			for (formValueIndex = 0; formValueIndex < formData.length; formValueIndex++) {
				formValue = formData[formValueIndex];

				if (keys.indexOf(formValue.name) !== -1) {
					result[formValue.name] = formValue.value;
				}
			}

			return result;
		},

		makeSign: function(data) {
			const keys = this.getUsedKeys();
			const parts = [];

			keys.push('page');

			for (let keyIndex = 0; keyIndex < keys.length; keyIndex++) {
				let key = keys[keyIndex];
				let value = data[key] || '';

				parts.push(key + '=' + value);
			}

			return parts.join('|');
		},

		getUsedKeys: function() {
			return this.options.usedKeys.split('|');
		},

		getFetcher: function() {
			const form = this.getForm();

			return Input.TradingOutletFetcher.getInstance(form);
		}

	}, {
		dataName: 'uiInputTradingOutlet'
	});

})(BX, window);