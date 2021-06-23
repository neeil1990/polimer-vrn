(function(BX, $, window) {

	const Plugin = BX.namespace('YandexMarket.Plugin');
	const Reference = BX.namespace('YandexMarket.Field.Reference');
	const OrderView = BX.namespace('YandexMarket.OrderView');

	const constructor = OrderView.BoxSize = Reference.Base.extend({

		defaults: {
			inputElement: '.js-yamarket-box-size__input',
			packElement: '.js-yamarket-box-size__pack-select',

			minDensity: 0.03,
			maxDensity: 21.5,

			lang: {},
			langPrefix: 'YANDEX_MARKET_T_TRADING_ORDER_VIEW_BOX_SIZE_'
		},

		initialize: function() {
			this.callParent('initialize', constructor);
			this.createPackSelect();
		},

		createPackSelect: function() {
			const element = this.getElement('pack');
			const pluginName = element.data('plugin');

			if (!pluginName) { return; }

			const plugin = Plugin.manager.getPlugin(pluginName);
			const instance = plugin.getInstance(element, true);
			const options = {
				field: this,
			};

			if (instance) {
				instance.setOptions(options);
			} else {
				new plugin(element, options);
			}
		},

		validate: function() {
			const o = this.options;
			const density = this.getDensity();

			if (o.minDensity != null && density < o.minDensity) {
				throw new Error(this.getLang('DENSITY_LESS_MINIMAL'));
			} else if (o.maxDensity != null && density > o.maxDensity) {
				throw new Error(this.getLang('DENSITY_MORE_MAXIMUM'));
			}
		},

		getDensity: function() {
			return this.getWeight() / this.getVolume();
		},

		getVolume: function() {
			return this.getSize('WIDTH') * this.getSize('HEIGHT') * this.getSize('DEPTH');
		},

		getWeight: function() {
			return this.getSize('WEIGHT');
		},

		getSize: function(key) {
			const input = this.getInput(key);
			let label;
			let value;

			if (input == null) {
				throw new Error(this.getLang('INPUT_NOT_FOUND', { KEY: key }));
			}

			value = parseFloat(input.val()) || 0;

			if (value <= 0) {
				label = this.getInputLabel(input) || key;
				throw new Error(this.getLang('SIZE_MUST_BE_POSITIVE', { LABEL: label }));
			}

			return value;
		},

		getInputLabel: function(input) {
			const label = input.siblings('label');
			let text = label.text() || '';

			text = text.replace(/,.*$/, '');

			return text;
		},

	}, {
		dataName: 'orderViewBoxSize',
		pluginName: 'YandexMarket.OrderView.BoxSize',
	});

})(BX, jQuery, window);