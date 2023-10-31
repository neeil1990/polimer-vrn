(function(BX, $, window) {

	const Plugin = BX.namespace('YandexMarket.Plugin');
	const FieldReference = BX.namespace('YandexMarket.Field.Reference');
	const OrderView = BX.namespace('YandexMarket.OrderView');

	const constructor = OrderView.BasketConfirmSummary = FieldReference.Summary.extend({

		defaults: {
			modalElement: '.js-yamarket-basket-confirm-summary__modal',
			fieldElement: '.js-yamarket-basket-confirm-summary__field',

			modalWidth: 500,
			modalHeight: 350,

			langPrefix: 'YANDEX_MARKET_T_TRADING_ORDER_VIEW_BASKET_CONFIRM_',
			lang: {},
		},

		initVars: function() {
			this.callParent('initVars', constructor);
			this._confirmDeferred = null;
		},

		onEditModalSave: function() {
			this.callParent('onEditModalSave', constructor);
			this.resolveConfirm();
		},

		onEditModalClose: function() {
			this.callParent('onEditModalClose', constructor);
			this.rejectConfirm();
		},

		validate: function() {
			// nothing
		},

		confirm: function(basket) {
			const changes = basket.getCountChanges();

			if (changes.length === 0) { return; }

			this.setItemsChanges(changes);
			this.initEdit();

			return this.waitConfirm();
		},

		setItemsChanges: function(changes) {
			this.callField('setItemsChanges', [changes]);
		},

		waitConfirm: function() {
			this._confirmDeferred = new $.Deferred();

			return this._confirmDeferred;
		},

		resolveConfirm: function() {
			const deferred = this._confirmDeferred;

			if (deferred === null) { return; }

			this._confirmDeferred = null;
			deferred.resolve();
		},

		rejectConfirm: function() {
			const deferred = this._confirmDeferred;

			if (deferred === null) { return; }

			this._confirmDeferred = null;
			deferred.reject();
		},

		getFieldPlugin: function() {
			return OrderView.BasketConfirmForm;
		},

	}, {
		dataName: 'orderViewBasketConfirmSummary',
		pluginName: 'YandexMarket.OrderView.BasketConfirmSummary',
	});

})(BX, jQuery, window);