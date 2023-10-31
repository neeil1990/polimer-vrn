(function(BX, $, window) {

	const Reference = BX.namespace('YandexMarket.Field.Reference');
	const OrderView = BX.namespace('YandexMarket.OrderView');

	const constructor = OrderView.BasketItemDigital = Reference.Base.extend({

		defaults: {
			inputElement: '.js-yamarket-basket-item-digital__input',

			lang: {},
			langPrefix: 'YANDEX_MARKET_T_TRADING_ORDER_VIEW_BASKET_ITEM_DIGITAL_'
		},

	}, {
		dataName: 'orderViewBasketItemDigital',
		pluginName: 'YandexMarket.OrderView.BasketItemDigital',
	});

})(BX, jQuery, window);