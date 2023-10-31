(function(BX, window) {

	var Reference = BX.namespace('YandexMarket.Field.Reference');
	var Fieldset = BX.namespace('YandexMarket.Field.Fieldset');

	var constructor = Fieldset.Row = Reference.Complex.extend({

		defaults: {
			elementDefault: '.js-fieldset',
			elementNamespace: null,

			inputElement: '.js-fieldset-row__input',
			childElement: '.js-fieldset-row__child',

			lang: {},
			langPrefix: 'YANDEX_MARKET_FIELD_FIELDSET_'
		},

		setOptions: function(options) {
			this.callParent('setOptions', [options], constructor);

			if (
				this.options.elementNamespace != null
				&& this.options.elementNamespace !== this.options.elementDefault
			) {
				this.overrideElementOptions(
					this.options.elementDefault,
					this.options.elementNamespace
				);
			}
		},

		overrideElementOptions: function(from, to) {
			var key;

			for (key in this.options) {
				if (!this.options.hasOwnProperty(key)) { continue; }
				if (key.indexOf('Element') === -1) { continue; }
				if (this.options[key] == null) { continue; }

				this.options[key] = this.options[key].replace(from, to);
			}
		},

	}, {
		dataName: 'FieldFieldsetRow',
		pluginName: 'YandexMarket.Field.Fieldset.Row'
	});

})(BX, window);