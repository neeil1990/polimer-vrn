(function(BX, $, window) {

	const Plugin = BX.namespace('YandexMarket.Plugin');
	const FieldReference = BX.namespace('YandexMarket.Field.Reference');
	const OrderView = BX.namespace('YandexMarket.OrderView');

	const constructor = OrderView.BasketConfirmForm = FieldReference.Base.extend({

		defaults: {
			inputElement: '.js-yamarket-basket-confirm-form__input',
			productsElement: '.js-yamarket-basket-confirm-form__products',

			langPrefix: 'YANDEX_MARKET_T_TRADING_ORDER_VIEW_BASKET_CONFIRM_',
			lang: {},
		},

		setItemsChanges: function(changes) {
			const element = this.getElement('products');
			const text = this.compileItemsChanges(changes);

			element.html(text);
		},

		compileItemsChanges: function(changes) {
			const rows = [];

			for (const change of changes) {
				rows.push(this.getLang('ITEM_CHANGE', {
					NAME: change.name,
					COUNT: change.diff,
				}));
			}

			return rows.join('<hr />');
		},

	}, {
		dataName: 'orderViewBasketConfirmForm',
		pluginName: 'YandexMarket.OrderView.BasketConfirmForm',
	});

})(BX, jQuery, window);