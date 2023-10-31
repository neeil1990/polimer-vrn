(function(BX, $, window) {

	const YandexMarket = BX.namespace('YandexMarket');
	const Reference = BX.namespace('YandexMarket.Field.Reference');
	const OrderView = BX.namespace('YandexMarket.OrderView');

	const constructor = OrderView.BasketItemCisSummary = Reference.Summary.extend({

		defaults: {
			summaryElement: '.js-yamarket-basket-item-cis__summary',
			fieldElement: '.js-yamarket-basket-item-cis__field',
			modalElement: '.js-yamarket-basket-item-cis__modal',
			modalWidth: 400,
			modalHeight: 300,
			count: 1,
			requiredTypes: '',

			copyElement: '.js-yamarket-basket-item-cis__summary-copy',
			copy: null,

			lang: {},
			langPrefix: 'YANDEX_MARKET_T_TRADING_ORDER_VIEW_BASKET_ITEM_CIS_'
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
			this.handleCopyClick(true);
		},

		unbind: function() {
			this.handleSummaryClick(false);
			this.handleCopyClick(false);
		},

		handleSummaryClick: function(dir) {
			const summary = this.getElement('summary');

			summary[dir ? 'on' : 'off']('click', $.proxy(this.onSummaryClick, this));
		},

		handleCopyClick: function(dir) {
			const copy = this.getElement('copy');

			copy[dir ? 'on' : 'off']('click', $.proxy(this.onCopyClick, this));
		},

		onSummaryClick: function(evt) {
			this.openEditModal();
			evt.preventDefault();
		},

		onCopyClick: function(evt) {
			this.copy();
			this.refreshSummary();
			evt.preventDefault();
		},

		validate: function() {
			const valueList = this.getValue();
			const status = this.getCisStatus(valueList);

			if (status === 'WAIT') {
				throw new Error(this.getLang('REQUIRED'));
			}
		},

		refreshSummary: function() {
			const valueList = this.getValue();
			const status = this.getCisStatus(valueList);
			const statusText = this.getLang('SUMMARY_' + status) || status;
			const summary = this.getElement('summary');

			summary.text(statusText);
			summary.attr('data-status', status);
		},

		getCisStatus: function(valueList) {
			const filled = this.getFilledCount(valueList);
			const total = this.getBasketCount();
			let result;

			if (filled >= total) {
				result = 'READY';
			} else {
				result = 'WAIT';
			}

			return result;
		},

		getFilledCount: function(valueList) {
			const total = this.options.count;
			const types = this.requiredTypes();
			let result = 0;

			for (let i = 0; i < total; ++i) {
				let itemFilled = 0;

				for (const type of types) {
					const name = `ITEMS[${i}][${type}]`;
					const value = valueList[name] ?? '';

					if (value.trim() !== '') {
						++itemFilled;
					}
				}

				if (itemFilled >= types.length) {
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

		requiredTypes: function() {
			const option = this.options.requiredTypes || '';

			return option.split(',');
		},

		copy: function() {
			const copyValues = this.getCopyValue();
			const values = this.getValue();

			Object.assign(values, copyValues);

			this.setValue(values);
		},

		getCopyValue: function() {
			return this.options.copy;
		},

		getFieldPlugin: function() {
			return OrderView.BasketItemCis;
		},

		createModal: function() {
			return new YandexMarket.EditDialog({
				'title': this.options.title,
				'draggable': true,
				'resizable': true,
				'buttons': [YandexMarket.EditDialog.btnSave, YandexMarket.EditDialog.btnCancel],
				'width': this.options.modalWidth,
				'height': this.options.modalHeight
			});
		},

	}, {
		dataName: 'orderViewBasketItemCisSummary',
		pluginName: 'YandexMarket.OrderView.BasketItemCisSummary',
	});

})(BX, jQuery, window);