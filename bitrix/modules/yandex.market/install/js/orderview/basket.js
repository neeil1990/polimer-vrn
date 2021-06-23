(function(BX, $, window) {

	const Plugin = BX.namespace('YandexMarket.Plugin');
	const FieldReference = BX.namespace('YandexMarket.Field.Reference');
	const OrderView = BX.namespace('YandexMarket.OrderView');

	const constructor = OrderView.Basket = FieldReference.Collection.extend({

		defaults: {
			itemElement: '.js-yamarket-basket-item'
		},

		validate: function() {
			this.callItemList(function(basketItem) {
				try {
					basketItem.validate()
				} catch (e) {
					const title = basketItem.getTitle();
					const message = (title ? title + ': ' : '') + e.message;

					throw new Error(message);
				}
			});
		},

		getItemPlugin: function() {
			return OrderView.BasketItem;
		}

	}, {
		dataName: 'orderViewBasket',
		pluginName: 'YandexMarket.OrderView.Basket',
	});

})(BX, jQuery, window);