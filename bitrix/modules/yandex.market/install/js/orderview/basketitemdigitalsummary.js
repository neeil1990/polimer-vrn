(function(BX, $, window) {

	const Reference = BX.namespace('YandexMarket.Field.Reference');
	const OrderView = BX.namespace('YandexMarket.OrderView');

	const constructor = OrderView.BasketItemDigitalSummary = Reference.Summary.extend({

		defaults: {
			summaryElement: '.js-yamarket-basket-item-digital__summary',
			fieldElement: '.js-yamarket-basket-item-digital__field',
			modalElement: '.js-yamarket-basket-item-digital__modal',
			modalWidth: 450,
			modalBaseHeight: 160,
			modalOneHeight: 140,
			count: 1,

			lang: {},
			langPrefix: 'YANDEX_MARKET_T_TRADING_ORDER_VIEW_BASKET_ITEM_DIGITAL_'
		},

		initVars: function() {
			this.callParent('initVars', constructor);
			this._basketCount = null;
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
			this.handleSummaryClick(true);
		},

		unbind: function() {
			this.handleSummaryClick(false);
		},

		handleSummaryClick: function(dir) {
			const summary = this.getElement('summary');

			summary[dir ? 'on' : 'off']('click', $.proxy(this.onSummaryClick, this));
		},

		onSummaryClick: function(evt) {
			this.resolveModalHeight();
			this.openEditModal();

			evt.preventDefault();
		},

		resolveModalHeight: function() {
			this.options.modalHeight = this.options.modalBaseHeight + (this.options.modalOneHeight * this.options.count);
		},

		validate: function() {
			const raw = this.getValue();
			const values = this.parseValue(raw);
			const status = this.getDigitalStatus(values);

			if (status === 'WAIT') {
				throw new Error(this.getLang('REQUIRED'));
			}
		},

		refreshSummary: function() {
			const raw = this.getValue();
			const values = this.parseValue(raw);
			const status = this.getDigitalStatus(values);
			const statusText = this.getLang('SUMMARY_' + status) || status;
			const summary = this.getElement('summary');

			summary.text(statusText);
			summary.attr('data-status', status);
		},

		parseValue: function(values) {
			const result = {};

			for (const key in values) {
				if (!values.hasOwnProperty(key)) { continue; }

				const keyChain = this.valueKeyChain(key);

				this.setValueByKeyChain(result, keyChain, values[key]);
			}

			return result;
		},

		valueKeyChain: function(key) {
			const parts = key.split('[');
			const result = [];

			for (const part of parts) {
				if (part === '' && result.length === 0) { continue; }

				const name = part.replace(/]$/, '');

				result.push(name);
			}

			return result;
		},

		setValueByKeyChain: function(result, chain, value) {
			const lastKey = chain.pop();
			let level = result;

			for (let index = 0; index < chain.length; ++index) {
				const key = chain[index];
				const nextKey = (index + 1 === chain.length) ? lastKey : chain[index + 1];

				if (level[key] == null) {
					level[key] = /^\d+$/.test(nextKey) ? [] : {};
				}

				level = level[key];
			}

			level[lastKey] = value;
		},

		getDigitalStatus: function(values) {
			const filled = this.getFilledCount(values);
			const total = this.getBasketCount();
			let result;

			if (filled >= total) {
				result = 'READY';
			} else {
				result = 'WAIT';
			}

			return result;
		},

		getFilledCount: function(values) {
			let result = 0;

			if (values['ITEM'] == null || !Array.isArray(values['ITEM'])) { return result; }

			for (const item of values['ITEM']) {
				if (item['CODE'] !== '' && item['ACTIVATE_TILL'] !== '') {
					++result;
				}
			}

			return result;
		},

		getBasketCount: function() {
			return (this._basketCount != null ? this._basketCount : this.options.count);
		},

		setBasketCount: function(count) {
			this._basketCount = count;
		},

		getFieldPlugin: function() {
			return OrderView.BasketItemDigital;
		},

	}, {
		dataName: 'orderViewBasketItemDigitalSummary',
		pluginName: 'YandexMarket.OrderView.BasketItemDigitalSummary',
	});

})(BX, jQuery, window);