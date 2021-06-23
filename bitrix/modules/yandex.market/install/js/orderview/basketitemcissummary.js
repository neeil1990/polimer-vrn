(function(BX, $, window) {

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
			required: false,

			copyElement: '.js-yamarket-basket-item-cis__summary-copy',
			copy: null,

			lang: {},
			langPrefix: 'YANDEX_MARKET_T_TRADING_ORDER_VIEW_BASKET_ITEM_CIS_'
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
			const total = this.options.count;
			const isRequired = this.options.required;
			let result;

			if (filled >= total) {
				result = 'READY';
			} else if (filled > 0 || isRequired) {
				result = 'WAIT';
			} else {
				result = 'EMPTY';
			}

			return result;
		},

		getFilledCount: function(valueList) {
			let result = 0;

			for (const index in valueList) {
				if (!valueList.hasOwnProperty(index)) { continue; }

				if (valueList[index] != null && valueList[index].trim() !== '') {
					++result;
				}
			}

			return result;
		},

		copy: function() {
			const value = this.getCopyValue();

			this.setValue(value);
		},

		getCopyValue: function() {
			return this.options.copy;
		},

		getFieldPlugin: function() {
			return OrderView.BasketItemCis;
		},

	}, {
		dataName: 'orderViewBasketItemCisSummary',
		pluginName: 'YandexMarket.OrderView.BasketItemCisSummary',
	});

})(BX, jQuery, window);